<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Writer;

use CliffordVickrey\Crosstabs\Crosstab\CrosstabInterface;
use CliffordVickrey\Crosstabs\Utilities\CrosstabExtractionUtilities;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_reduce;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function trim;

/**
 * Uses inheritance (rather than composition) to write a table with Bootstrap table and utility classes. Uncle Bob can
 * sue me
 */
final class CrosstabBootstrapHtmlWriter extends CrosstabHtmlWriter
{
    /**
     * @param int<4, 5> $bootstrapVersion
     */
    public function __construct(private readonly int $bootstrapVersion = 5)
    {
    }

    /**
     * @param list<string> $carry
     * @param string $borderType
     * @return list<string>
     */
    private static function remapBorderTypesToBootstrap5(array $carry, string $borderType): array
    {
        if ('left' === $borderType) {
            return [...$carry, 'start'];
        }

        if ('right' === $borderType) {
            return [...$carry, 'end'];
        }

        return [...$carry, $borderType];
    }

    /**
     * @inheritDoc
     */
    public function write(CrosstabInterface $crosstab, array $options = []): string
    {
        $tableAttributes = (array_key_exists(self::TABLE_ATTRIBUTES, $options)
            && is_array($options[self::TABLE_ATTRIBUTES])) ? $options[self::TABLE_ATTRIBUTES] : [];

        $tableClass = trim(CrosstabExtractionUtilities::extractString('class', $tableAttributes));
        $tableClasses = explode(' ', $tableClass);

        if (!in_array('table', $tableClasses)) {
            $tableClasses[] = 'table';
        }

        $tableAttributes['class'] = implode(' ', $tableClasses);

        $options = array_merge($options, [
            self::WITH_DEFAULT_STYLES => false,
            self::TABLE_ATTRIBUTES => $tableAttributes
        ]);

        return parent::write($this->crosstabWithBootstrapClasses($crosstab), $options);
    }

    /**
     * @param CrosstabInterface $crosstab
     * @return CrosstabInterface
     */
    private function crosstabWithBootstrapClasses(CrosstabInterface $crosstab,): CrosstabInterface
    {
        $bootstrapCrosstab = clone $crosstab;

        foreach ($bootstrapCrosstab as $row) {
            foreach ($row as $cell) {
                $class = trim(CrosstabExtractionUtilities::extractString('class', $cell->attributes));
                $classes = explode(' ', $class);

                $classes = [
                    ...$classes,
                    ...$this->getBootstrapBorderClasses($classes),
                    $this->getTextAlignClass($classes),
                    $this->getVerticalAlignClass($classes)
                ];

                $cell->attributes['class'] = implode(' ', $classes);
            }
        }

        return $bootstrapCrosstab;
    }

    /**
     * @param list<string> $classes
     * @return list<string>
     */
    private function getBootstrapBorderClasses(array $classes): array
    {
        $border = $this->extractBordersFromClasses($classes);

        if (4 === count($border)) {
            return ['border'];
        }

        $borderClasses = [];

        $borderTypes = array_keys($border);

        if ($this->bootstrapVersion > 4) {
            $borderTypes = array_reduce($borderTypes, self::remapBorderTypesToBootstrap5(...), []);
        }

        foreach ($borderTypes as $borderType) {
            $borderClasses[] = "border-$borderType";
        }

        return $borderClasses;
    }

    /**
     * Resolve a table cell's text alignment CSS
     * @param list<string> $classes
     * @return string
     */
    private function getTextAlignClass(array $classes): string
    {
        $bootstrap5 = $this->bootstrapVersion > 4;

        foreach ($classes as $class) {
            if (in_array($class, $this->classesWithTextAlignment['center'])) {
                return 'text-center';
            }

            if (in_array($class, $this->classesWithTextAlignment['right'])) {
                return 'text-' . ($bootstrap5 ? 'end' : 'right');
            }
        }

        return 'text-' . ($bootstrap5 ? 'start' : 'left');
    }

    /**
     * @param list<string> $classes
     * @return string
     */
    private function getVerticalAlignClass(array $classes): string
    {
        foreach ($classes as $class) {
            if (in_array($class, $this->classesWithVerticalAlignment['bottom'])) {
                return 'align-bottom';
            }

            if (in_array($class, $this->classesWithVerticalAlignment['middle'])) {
                return 'align-middle';
            }
        }

        return 'align-top';
    }

    /**
     * @inheritDoc
     */
    protected function openWrapper(bool $pretty): string
    {
        return $this->openTag('div', $pretty, ['class' => 'table-responsive']);
    }

    /**
     * @return string
     */
    protected function getTemplate(): string
    {
        $version = 5 === $this->bootstrapVersion ? '5' : '4';
        return $this->fileGetContents(__DIR__ . "/../../templates/crosstab-bootstrap$version.html");
    }
}

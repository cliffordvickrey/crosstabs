<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Writer;

use CliffordVickrey\Crosstabs\Crosstab\CrosstabCell;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabInterface;
use CliffordVickrey\Crosstabs\Utilities\CrosstabCastingUtilities;
use CliffordVickrey\Crosstabs\Utilities\CrosstabExtractionUtilities;

use function array_combine;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pad;
use function array_sum;
use function array_values;
use function bcdiv;
use function bcmul;
use function count;
use function explode;
use function htmlentities;
use function in_array;
use function is_array;
use function is_object;
use function rtrim;
use function str_repeat;
use function str_replace;
use function trim;

use const ENT_QUOTES;
use const PHP_EOL;

/**
 * Writes a crosstab a HTML
 */
class CrosstabHtmlWriter extends AbstractCrosstabWriter
{
    public const COL_WIDTH_FACTORS = 'colWidthFactors'; // option for weighting the width of columns
    public const HTML_HEAD_TITLE = 'htmlHeadTitle'; // <title> to use when writing to an HTML file
    public const HTML_LANG = 'htmlLang'; // <html> lang attribute to use when writing to a file
    public const PRETTY = 'pretty'; // whether to prettify the output (defaults to TRUE)
    public const RESPONSIVE = 'responsive'; // whether to wrap the table in an X-scrollable <div> element
    public const TABLE_ATTRIBUTES = 'tableAttributes'; // <table> HTML attributes (array<string, string>)
    public const TBODY_ATTRIBUTES = 'tbodyAttributes'; // <tbody> HTML attributes (array<string, string>)
    public const TD_ATTRIBUTES = 'tdAttributes';  // <td> HTML attributes (array<string, string>)
    public const TH_ATTRIBUTES = 'thAttributes'; // <th> HTML attributes (array<string, string>)
    public const THEAD_ATTRIBUTES = 'theadAttributes'; // <thead> HTML attributes (array<string, string>)
    public const TR_ATTRIBUTES = 'trAttributes'; // <tr> HTML attributes (array<string, string>)
    public const WITH_DEFAULT_STYLES = 'withDefaultStyles'; // whether to use default inline CSS (defaults to TRUE)
    private const INDENTATION = '    ';

    /** @var array<string, int> */
    private static array $indentations = [
        'colgroup' => 1,
        'col' => 2,
        'div' => 0,
        'table' => 0,
        'tbody' => 1,
        'td' => 2,
        'th' => 2,
        'thead' => 1,
        'tr' => 1
    ];
    /** @var list<string> */
    private static array $tagsWithNewLines = [
        'div',
        'col',
        'colgroup',
        'table',
        'tbody',
        'thead',
        'tr'
    ];

    /** @var array{
     *     all: list<string>,
     *     top: list<string>,
     *     right: list<string>,
     *     bottom: list<string>,
     *     left: list<string>
     * }
     */
    private static array $classesWithBorders = [
        'all' => [
            CrosstabCell::APPEARANCE_X_AXIS,
            CrosstabCell::APPEARANCE_X_AXIS_CATEGORY_LABEL,
            CrosstabCell::APPEARANCE_Y_AXIS
        ],
        'top' => [],
        'right' => [
            CrosstabCell::APPEARANCE_CELL,
            CrosstabCell::APPEARANCE_DATA_TYPE,
            CrosstabCell::APPEARANCE_Y_AXIS_CATEGORY_LABEL_SIMPLE
        ],
        'bottom' => [
            CrosstabCell::APPEARANCE_BOTTOM_CELL,
            CrosstabCell::APPEARANCE_Y_AXIS_CATEGORY_LABEL,
            CrosstabCell::APPEARANCE_Y_AXIS_VARIABLE_LABEL
        ],
        'left' => []
    ];

    /** @var array{center: list<string>, left: list<string>, right: list<string>} */
    private static array $classesWithTextAlignment = [
        'center' => [
            CrosstabCell::APPEARANCE_TITLE,
            CrosstabCell::APPEARANCE_X_AXIS,
            CrosstabCell::APPEARANCE_X_AXIS_CATEGORY_LABEL,
            CrosstabCell::APPEARANCE_Y_AXIS
        ],
        'left' => [],
        'right' => [CrosstabCell::APPEARANCE_CELL]
    ];

    /** @var array{top: list<string>, middle: list<string>, bottom: list<string>} */
    private static array $classesWithVerticalAlignment = [
        'top' => [],
        'middle' => [],
        'bottom' => [CrosstabCell::APPEARANCE_Y_AXIS]
    ];

    /**
     * @inheritDoc
     */
    public function write(CrosstabInterface $crosstab, array $options = []): string
    {
        if (0 === count($crosstab)) {
            return '';
        }

        $options = self::parseOptions($options);
        $pretty = (bool)($options[self::PRETTY] ?? true);
        $withDefaultStyles = (bool)($options[self::WITH_DEFAULT_STYLES] ?? true);
        $responsive = (bool)($options[self::RESPONSIVE] ?? false);

        $html = '';

        if ($responsive) {
            // wrap the table in an X-scrollable div
            $html = self::openTag('div', $pretty, [
                'class' => '__crosstab-wrapper',
                'style' => 'overflow-x:auto'
            ]);
        }

        $html .= self::openTag('table', $pretty, $options[self::TABLE_ATTRIBUTES] ?? []);

        $theadOpened = false;
        $theadClosed = false;

        foreach ($crosstab as $row) {
            if (!$theadOpened) {
                // write the col widths and open <thead>
                $colWidths = self::getColWidths($row->getWidth(), $options);

                $html .= self::openTag('colgroup', $pretty);

                foreach ($colWidths as $colWidth) {
                    $html .= self::openTag('col', $pretty, ['style' => "width:$colWidth;"]);
                }

                $html .= self::closeTag('colgroup', $pretty);

                $html .= self::openTag('thead', $pretty, $options[self::THEAD_ATTRIBUTES] ?? []);

                $theadOpened = true;
            } elseif (!$theadClosed && !$row->isHeader()) {
                // close <thead> and open <tbody>
                $html .= self::closeTag('thead', $pretty);
                $html .= self::openTag('tbody', $pretty, $options[self::TBODY_ATTRIBUTES] ?? []);
                $theadClosed = true;
            }

            // write the row
            $html .= self::openTag('tr', $pretty, $options[self::TR_ATTRIBUTES] ?? []);

            foreach ($row as $cell) {
                // write the cell
                $html .= self::writeCell(
                    $cell,
                    $options[$cell->isHeader ? self::TH_ATTRIBUTES : self::TD_ATTRIBUTES] ?? [],
                    $pretty,
                    $withDefaultStyles
                );
            }

            // close the row
            $html .= self::closeTag('tr', $pretty);
        }

        // close all open tags
        if ($theadClosed) {
            $html .= self::closeTag('tbody', $pretty);
        } elseif ($theadOpened) {
            $html .= self::closeTag('thead', $pretty);
        }

        return trim($html . self::closeTag('table', $pretty) . ($responsive ? self::closeTag('div', $pretty) : ''));
    }

    /**
     * Populates the options with default styles, if desired
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private static function parseOptions(array $options): array
    {
        $withDefaultStyles = (bool)($options[self::WITH_DEFAULT_STYLES] ?? true);

        $tableAttributes = (array_key_exists(self::TABLE_ATTRIBUTES, $options)
            && is_array($options[self::TABLE_ATTRIBUTES])) ? $options[self::TABLE_ATTRIBUTES] : [];

        if (!isset($tableAttributes['class'])) {
            $tableAttributes['class'] = '__crosstab';
        }

        if (!$withDefaultStyles) {
            return $options;
        }

        $tableStyle = trim(CrosstabExtractionUtilities::extractString('style', $tableAttributes));

        if ('' !== $tableStyle) {
            $tableStyle .= ';';
        }

        $tableStyle .= 'border-spacing:0;border-collapse:collapse;border:1px solid black;table-layout:fixed;';
        $tableAttributes['style'] = $tableStyle;
        $options[self::TABLE_ATTRIBUTES] = $tableAttributes;
        return $options;
    }

    /**
     * Opens an HTML tag
     * @param literal-string $tagName Literal, because we don't escape it!
     * @param bool $pretty
     * @param mixed $attributes
     * @return string
     */
    private static function openTag(string $tagName, bool $pretty, mixed $attributes = []): string
    {
        if (is_object($attributes)) {
            $attributes = (array)$attributes;
        }

        if (!is_array($attributes)) {
            $attributes = [];
        }

        $indentation = self::$indentations[$tagName] ?? 0;

        $html = ($pretty ? str_repeat(self::INDENTATION, $indentation) : '') . "<$tagName";

        /** @psalm-suppress MixedAssignment */
        foreach ($attributes as $prop => $value) {
            $prop = trim((string)$prop);

            if ('' === $prop) {
                continue;
            }

            $propHtml = self::htmlEncode($prop);
            $valueHtml = self::htmlEncode(CrosstabCastingUtilities::toString($value));

            $html .= " $propHtml=\"$valueHtml\"";
        }

        $eol = ($pretty && in_array($tagName, self::$tagsWithNewLines)) ? PHP_EOL : '';

        return "$html>" . $eol;
    }

    /**
     * @param string $textContent
     * @return string
     */
    private static function htmlEncode(string $textContent): string
    {
        return htmlentities($textContent, ENT_QUOTES);
    }

    /**
     * Computes the widths for each column from options. If no option passed, make each column equal
     * @param int<0, max> $width
     * @param array<string, mixed> $options
     * @return list<non-empty-string>
     */
    private function getColWidths(int $width, array $options): array
    {
        $colWidthFactors = (array_key_exists(self::COL_WIDTH_FACTORS, $options)
            && is_array($options[self::COL_WIDTH_FACTORS])) ? $options[self::COL_WIDTH_FACTORS] : [];

        $colWidthFactors = array_pad(
            array_filter(array_map(CrosstabCastingUtilities::toNumeric(...), $colWidthFactors)),
            $width,
            1
        );

        $sum = (string)array_sum($colWidthFactors);

        return array_map(
            fn($factor) => rtrim(rtrim(bcmul(bcdiv((string)$factor, $sum, 4), '100', 2), '0'), '.') . '%',
            array_values($colWidthFactors)
        );
    }

    /**
     * @param literal-string $tagName Literal, because we don't escape it!
     * @param bool $pretty
     * @return string
     */
    private static function closeTag(string $tagName, bool $pretty): string
    {
        $indentation = self::$indentations[$tagName] ?? 0;

        if (!$pretty || !in_array($tagName, self::$tagsWithNewLines)) {
            $indentation = 0;
        }

        return str_repeat(self::INDENTATION, $indentation) . "</$tagName>" . ($pretty ? PHP_EOL : '');
    }

    /**
     * @param CrosstabCell $cell
     * @param mixed $defaultAttributes
     * @param bool $pretty
     * @param bool $withDefaultStyles
     * @return string
     */
    private static function writeCell(
        CrosstabCell $cell,
        mixed $defaultAttributes,
        bool $pretty,
        bool $withDefaultStyles
    ): string {
        $attributes = self::getCellAttributes($cell, $defaultAttributes, $withDefaultStyles);

        $innerHtml = self::htmlEncode($cell->textContent);

        $tagName = $cell->isHeader ? 'th' : 'td';

        return self::openTag($tagName, $pretty, $attributes) . $innerHtml . self::closeTag($tagName, $pretty);
    }

    /**
     * Make a heroic effort to determine how to style each table header and cell, using the options passed to the write
     * @param CrosstabCell $cell
     * @param mixed $defaultAttributes
     * @param bool $withDefaultStyles
     * @return array<string, mixed>
     */
    private static function getCellAttributes(
        CrosstabCell $cell,
        mixed $defaultAttributes,
        bool $withDefaultStyles
    ): array {
        if (is_object($defaultAttributes)) {
            $defaultAttributes = (array)$defaultAttributes;
        }

        if (!is_array($defaultAttributes)) {
            $defaultAttributes = [];
        }

        $attributes = array_merge($cell->attributes, $defaultAttributes);

        if (isset($defaultAttributes['class']) && !empty($cell->attributes['class'])) {
            $attributes['class'] = CrosstabCastingUtilities::toString($attributes['class'])
                . ' '
                . $cell->attributes['class'];
        }

        $spans = array_map(strval(...), array_filter(
            ['colspan' => $cell->colspan, 'rowspan' => $cell->rowspan],
            static fn($span) => $span > 1
        ));

        $attr = array_merge($attributes, $spans);

        $attr = array_combine(array_map(strval(...), array_keys($attr)), $attr);

        if (!$withDefaultStyles) {
            return $attr;
        }

        $style = trim(CrosstabExtractionUtilities::extractString('style', $defaultAttributes));

        if ('' !== $style) {
            $style .= ';';
        }

        $style .= 'white-space:normal';

        $class = trim(CrosstabExtractionUtilities::extractString('class', $attr));
        $classes = array_values(array_filter(explode(' ', $class)));

        $attr['style'] = $style
            . self::getBorderStyle($classes)
            . self::getTextAlignStyle($classes)
            . self::getVerticalAlignStyle($classes);

        return $attr;
    }

    /**
     * Resolve CSS for a cell's borders
     * @param list<string> $classes
     * @return string
     */
    private static function getBorderStyle(array $classes): string
    {
        $border = ['top' => false, 'right' => false, 'bottom' => false, 'left' => false];

        $borderTypes = array_keys($border);

        foreach ($classes as $class) {
            if (in_array($class, self::$classesWithBorders['all'])) {
                $border = ['top' => true, 'right' => true, 'bottom' => true, 'left' => true];
                break;
            }

            foreach ($borderTypes as $borderType) {
                if (in_array($class, self::$classesWithBorders[$borderType])) {
                    $border[$borderType] = true;
                }
            }
        }

        $border = array_filter($border);

        if (0 === count($border)) {
            return '';
        }

        if (4 === count($border)) {
            return ';border:1px solid black';
        }

        $style = '';

        $borderTypes = array_keys($border);

        foreach ($borderTypes as $borderType) {
            $style .= ";border-$borderType:1px solid black";
        }

        return $style;
    }

    /**
     * Resolve a table cell's text alignment CSS
     * @param list<string> $classes
     * @return string
     */
    private static function getTextAlignStyle(array $classes): string
    {
        foreach ($classes as $class) {
            if (in_array($class, self::$classesWithTextAlignment['center'])) {
                return ';text-align:center';
            }

            if (in_array($class, self::$classesWithTextAlignment['right'])) {
                return ';text-align:right';
            }
        }

        return ';text-align:left';
    }

    /**
     * Resolve a table cell's vertical alignment CSS
     * @param list<string> $classes
     * @return string
     */
    private static function getVerticalAlignStyle(array $classes): string
    {
        foreach ($classes as $class) {
            if (in_array($class, self::$classesWithVerticalAlignment['bottom'])) {
                return ';vertical-align:bottom';
            }

            if (in_array($class, self::$classesWithVerticalAlignment['middle'])) {
                return ';vertical-align:middle';
            }
        }

        return ';vertical-align:top';
    }

    /**
     * Interpolates our HTML template with our parameters
     * @param string $output
     * @param array<string, mixed> $options
     * @return string
     */
    protected function prepareOutputForFile(string $output, array $options): string
    {
        $tpl = $this->fileGetContents(__DIR__ . '/../../templates/crosstab.html');

        $lang = CrosstabExtractionUtilities::extractString(self::HTML_LANG, $options) ?: 'en';
        $title = CrosstabExtractionUtilities::extractString(self::HTML_HEAD_TITLE, $options) ?: 'Crosstab';

        return str_replace(['%lang%', '%title%', '%crosstab%'], [$lang, $title, $output], $tpl);
    }
}

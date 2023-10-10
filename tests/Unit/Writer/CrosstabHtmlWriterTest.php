<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Writer;

use CliffordVickrey\Crosstabs\Crosstab\Crosstab;
use CliffordVickrey\Crosstabs\Tests\Provider\TestDataProvider;
use CliffordVickrey\Crosstabs\Writer\AbstractCrosstabWriter;
use CliffordVickrey\Crosstabs\Writer\CrosstabHtmlWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function strlen;

#[CoversClass(CrosstabHtmlWriter::class)]
#[CoversClass(AbstractCrosstabWriter::class)]
class CrosstabHtmlWriterTest extends TestCase
{
    private static int $htmlLength = 0;
    /**
     * @var Crosstab
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private Crosstab $crosstab;
    /**
     * @var CrosstabHtmlWriter
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabHtmlWriter $writer;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $provider = new TestDataProvider();
        $this->crosstab = $provider->getCrosstab();
        $this->writer = new CrosstabHtmlWriter();
    }

    /**
     * @return void
     */
    public function testWrite(): void
    {
        $html = $this->doWrite(__FUNCTION__);
        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringNotContainsString('overflow-x:auto', $html);
        self::$htmlLength = strlen($html);
    }

    /**
     * @param string $name
     * @param array<string, mixed> $options
     * @return string
     */
    private function doWrite(string $name, array $options = []): string
    {
        $filename = __DIR__ . "/../../output/$name.html";
        $savedTo = $this->writer->writeToFile($this->crosstab, $filename, $options);
        self::assertFileExists($savedTo);
        return (string)file_get_contents($savedTo);
    }

    /**
     * @return void
     */
    #[Depends('testWrite')]
    public function testWriteResponsive(): void
    {
        $html = $this->doWrite(__FUNCTION__, [CrosstabHtmlWriter::RESPONSIVE => true]);
        self::assertStringContainsString('overflow-x:auto', $html);
    }

    /**
     * @return void
     */
    #[Depends('testWrite')]
    public function testWriteSansPretty(): void
    {
        $html = $this->doWrite(__FUNCTION__, [CrosstabHtmlWriter::PRETTY => false]);
        self::assertLessThan(self::$htmlLength, strlen($html));
    }

    /**
     * @return void
     */
    #[Depends('testWrite')]
    public function testWriteSansDefaultStyles(): void
    {
        $html = $this->doWrite(__FUNCTION__, [CrosstabHtmlWriter::WITH_DEFAULT_STYLES => false]);
        self::assertLessThan(self::$htmlLength, strlen($html));
    }

    /**
     * @return void
     */
    #[Depends('testWrite')]
    public function testWriteWithStyleOptions(): void
    {
        $html = $this->doWrite(__FUNCTION__, [
            CrosstabHtmlWriter::TABLE_ATTRIBUTES => ['style' => 'color:red !important;'],
            CrosstabHtmlWriter::TD_ATTRIBUTES => ['class' => 'blah', 'style' => 'color:blue !important;']
        ]);
        self::assertStringContainsString('color:blue !important;', $html);
        self::assertStringContainsString('color:red !important;', $html);
        self::assertStringContainsString('blah', $html);
    }

    /**
     * @return void
     */
    public function testWriteEmpty(): void
    {
        self::assertEquals('', $this->writer->write(new Crosstab([], [])));
    }
}

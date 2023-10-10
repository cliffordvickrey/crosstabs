<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Writer;

use CliffordVickrey\Crosstabs\Crosstab\Crosstab;
use CliffordVickrey\Crosstabs\Tests\Provider\TestDataProvider;
use CliffordVickrey\Crosstabs\Writer\CrosstabBootstrapHtmlWriter;
use CliffordVickrey\Crosstabs\Writer\CrosstabHtmlWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function ucfirst;

#[CoversClass(CrosstabBootstrapHtmlWriter::class)]
class CrosstabBoostrapHtmlWriterTest extends TestCase
{
    /**
     * @var CrosstabBootstrapHtmlWriter
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabBootstrapHtmlWriter $bootstrap4HtmlWriter;

    /**
     * @var CrosstabBootstrapHtmlWriter
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabBootstrapHtmlWriter $bootstrap5HtmlWriter;

    /**
     * @var Crosstab
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private Crosstab $crosstab;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $provider = new TestDataProvider();
        $this->crosstab = $provider->getCrosstab();
        $this->bootstrap4HtmlWriter = new CrosstabBootstrapHtmlWriter(4);
        $this->bootstrap5HtmlWriter = new CrosstabBootstrapHtmlWriter();
    }

    /**
     * @return void
     */
    public function testWrite(): void
    {
        $output = $this->doWrite(__FUNCTION__);

        list ($html4, $html5) = $output;

        self::assertStringStartsWith('<!DOCTYPE html>', $html4);
        self::assertStringStartsWith('<!DOCTYPE html>', $html5);
        self::assertStringNotContainsString('table-responsive', $html4);
        self::assertStringNotContainsString('table-responsive', $html5);
        self::assertStringContainsString('border-right', $html4);
        self::assertStringNotContainsString('border-right', $html5);
        self::assertStringNotContainsString('border-end', $html4);
        self::assertStringContainsString('border-end', $html5);
    }

    /**
     * @param string $name
     * @param array<string, mixed> $options
     * @return array{0: string, 1: string}
     */
    private function doWrite(string $name, array $options = []): array
    {
        $name = ucfirst($name);

        $filename = __DIR__ . "/../../output/bootstrap4$name.html";
        $savedTo = $this->bootstrap4HtmlWriter->writeToFile($this->crosstab, $filename, $options);
        self::assertFileExists($savedTo);
        $htmlBs4 = (string)file_get_contents($savedTo);

        $filename = __DIR__ . "/../../output/bootstrap5$name.html";
        $savedTo = $this->bootstrap5HtmlWriter->writeToFile($this->crosstab, $filename, $options);
        self::assertFileExists($savedTo);
        $htmlBs5 = (string)file_get_contents($savedTo);

        return [$htmlBs4, $htmlBs5];
    }

    /**
     * @return void
     */
    #[Depends('testWrite')]
    public function testWriteResponsive(): void
    {
        $output = $this->doWrite(__FUNCTION__, [CrosstabHtmlWriter::RESPONSIVE => true]);

        list ($html4, $html5) = $output;

        self::assertStringContainsString('table-responsive', $html4);
        self::assertStringContainsString('table-responsive', $html5);
    }
}

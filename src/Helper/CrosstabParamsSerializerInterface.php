<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Helper;

/**
 * @internal
 */
interface CrosstabParamsSerializerInterface
{
    /**
     * Simple: converts the params of a cell in a crosstab (including marginal and grand totals) to a string
     * @param array<string, string|null> $params
     * @return string
     */
    public function serializeParams(array $params): string;
}

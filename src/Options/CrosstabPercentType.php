<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Options;

enum CrosstabPercentType: string
{
    case COLUMN = 'column'; // column percentages
    case COLUMN_WITHIN_LAYER = 'columnWithinLayer'; // column percentages for each layer (in layered crosstabs)
    case TOTAL = 'total'; // total percentages
    case TOTAL_WITHIN_LAYER = 'totalWithinLayer'; // total percentages for each layer (in layered crosstabs)
    case ROW = 'row'; // row percentages
}

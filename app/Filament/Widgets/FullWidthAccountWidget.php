<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget as BaseAccountWidget;

class FullWidthAccountWidget extends BaseAccountWidget
{
    // Make the account widget span the full width of the dashboard grid
    protected int | string | array $columnSpan = 'full';
}

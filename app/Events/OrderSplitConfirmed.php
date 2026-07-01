<?php

namespace App\Events;

use App\Models\OrderSplit;

class OrderSplitConfirmed
{
    public function __construct(
        public readonly OrderSplit $split,
    ) {}
}

<?php

namespace App\Listeners;

use App\Events\OrderSplitReverted;
use App\Services\AvaEnrollmentService;

class HandleAvaEnrollmentOnSplitReverted
{
    public function __construct(
        private readonly AvaEnrollmentService $service,
    ) {}

    public function handle(OrderSplitReverted $event): void
    {
        $this->service->revokeFromOrderSplit($event->split);
    }
}

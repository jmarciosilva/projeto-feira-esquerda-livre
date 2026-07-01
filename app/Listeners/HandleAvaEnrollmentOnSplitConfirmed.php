<?php

namespace App\Listeners;

use App\Events\OrderSplitConfirmed;
use App\Services\AvaEnrollmentService;

class HandleAvaEnrollmentOnSplitConfirmed
{
    public function __construct(
        private readonly AvaEnrollmentService $service,
    ) {}

    public function handle(OrderSplitConfirmed $event): void
    {
        $this->service->createFromOrderSplit($event->split);
    }
}

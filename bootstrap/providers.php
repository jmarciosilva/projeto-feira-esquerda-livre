<?php

use App\CatalogIntelligence\CatalogIntelligenceServiceProvider;
use App\CustomerIntelligence\CustomerIntelligenceServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CustomerIntelligenceServiceProvider::class,
    CatalogIntelligenceServiceProvider::class,
];

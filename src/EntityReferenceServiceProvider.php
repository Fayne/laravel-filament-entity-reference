<?php

namespace Fayne\EntityReference;

use Illuminate\Support\ServiceProvider;

class EntityReferenceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-entity-reference');
    }
}

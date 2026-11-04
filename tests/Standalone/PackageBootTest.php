<?php

namespace Fayne\EntityReference\Tests\Standalone;

use Fayne\EntityReference\EntityReferenceServiceProvider;
use Orchestra\Testbench\TestCase;

class PackageBootTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [EntityReferenceServiceProvider::class];
    }

    public function test_package_registers_its_resources_without_a_host_panel(): void
    {
        $this->assertNotSame('filament-entity-reference::entity-reference.reference_invalid', trans('filament-entity-reference::entity-reference.reference_invalid'));
    }
}

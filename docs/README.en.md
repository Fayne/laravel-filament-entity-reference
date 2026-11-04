<p align="center"><img src="https://res.cloudinary.com/fayne/image/upload/v1787643269/fayne_logo.png" width="300"></p>

# Laravel Filament Entity Reference

[简体中文](../README.md) | English

For **PHP 8.4+, Laravel 13 and Filament 5**.

## Installation

Configure authorized Composer sources for this private package and its required `fayne/*` dependencies in the host first.

```bash
composer require fayne/laravel-filament-entity-reference
```

Laravel discovers the service provider automatically. Field-only packages need no Panel plugin. See [composer.json](../composer.json) for exact dependencies; do not copy private credentials from a development host.

## Features, usage and boundaries

Provides an authorized polymorphic `EntityReferenceSelect` for Filament forms. Applications supply an explicit alias-to-model map, a scoped query callback, and a record authorization callback. Stored references contain only the allowlisted alias and record key.

```php
use App\Models\Product;
use Fayne\EntityReference\Forms\Components\EntityReferenceSelect;
use Illuminate\Database\Eloquent\Model;

EntityReferenceSelect::make('related')
    ->references(['product' => ['model' => Product::class, 'label' => 'title']])
    ->queryUsing(fn (string $model, string $alias) => $model::query())
    ->authorizeUsing(fn (Model $record) => auth()->user()?->can('view', $record) ?? false);
```

Search, selected-value hydration, and submitted values all use the same scoped query and authorization callbacks. Do not accept model class names from client state.

## Testing and deployment

The package includes standalone Testbench boot tests (`composer test`). Verify rendering, input, save/reload, invalid values, and authorization in the host. Documentation checks are not runtime acceptance.

## Cooperation and contributions

Open an issue with reproduction steps, versions and expected behavior. Include relevant checks and outstanding environment acceptance in pull requests. Never submit credentials or sensitive business data. For commercial cooperation and licensing, contact [Fayne Guo](mailto:fayne.guo@gmail.com).

## License

[Proprietary license](../LICENSE.md). Copying, distribution and use require authorization. Third-party dependencies retain their own licenses.

## Development and security

[Development and CI](../CONTRIBUTING.md) · [Security reports](../SECURITY.md). Run `composer check` for syntax, formatting and available standalone tests. Do not commit `composer.lock`.

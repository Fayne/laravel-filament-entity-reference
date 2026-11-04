<?php

namespace Fayne\EntityReference\Forms\Components;

use Closure;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class EntityReferenceSelect extends Select
{
    protected array $references = [];

    protected ?Closure $queryCallback = null;

    protected ?Closure $authorizationCallback = null;

    protected ?Closure $labelCallback = null;

    public function references(array $references, bool $multiple = false): static
    {
        foreach ($references as $alias => $definition) {
            $model = $definition['model'] ?? null;
            $label = $definition['label'] ?? null;
            if (! is_string($alias) || ! is_string($model) || ! is_subclass_of($model, Model::class) || ! is_string($label) || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $label)) {
                throw new InvalidArgumentException(__('filament-entity-reference::entity-reference.definition_invalid'));
            }
            $this->references[$alias] = ['model' => $model, 'label' => $label];
        }
        if ($this->references === []) {
            throw new InvalidArgumentException(__('filament-entity-reference::entity-reference.references_required'));
        }

        $this->multiple($multiple)->searchable();
        $this->dehydrateStateUsing(fn ($state) => $this->dehydrateReferences($state));
        $this->rule(fn (EntityReferenceSelect $component): Closure => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            $values = $component->isMultiple() ? ($value ?? []) : [$value];
            if (! is_array($values)) {
                $fail(__('filament-entity-reference::entity-reference.reference_invalid'));

                return;
            }
            foreach ($values as $reference) {
                if (($component->isMultiple() && $reference === null) || ($reference !== null && $component->labelFor($reference) === null)) {
                    $fail(__('filament-entity-reference::entity-reference.reference_invalid'));

                    return;
                }
            }
        });
        $this->getSearchResultsUsing(fn (string $search): array => $this->search($search));
        $this->getOptionLabelUsing(fn ($value): ?string => $this->labelFor($value));
        $this->getOptionLabelsUsing(function (array $values): array {
            $labels = [];
            foreach ($values as $value) {
                if (($label = $this->labelFor($value)) !== null) {
                    $labels[$value] = $label;
                }
            }

            return $labels;
        });

        return $this;
    }

    public function queryUsing(Closure $callback): static
    {
        $this->queryCallback = $callback;

        return $this;
    }

    public function authorizeUsing(Closure $callback): static
    {
        $this->authorizationCallback = $callback;

        return $this;
    }

    public function labelUsing(Closure $callback): static
    {
        $this->labelCallback = $callback;

        return $this;
    }

    public function hydrateState(?array &$hydratedDefaultState, bool $shouldCallHydrationHooks = true, bool $shouldApplyStateCasts = true, array &$appliedStateCastPaths = []): void
    {
        $value = $this->getRawState();
        $value = is_string($value) ? ($value === '' ? null : json_decode($value, true, flags: JSON_THROW_ON_ERROR)) : $value;
        $items = empty($value) ? [] : (array_is_list($value) ? $value : [$value]);
        $keys = array_map(fn ($item) => json_encode(is_array($item) ? ['model' => $item['model'] ?? null, 'id' => $item['id'] ?? null] : [], JSON_THROW_ON_ERROR), $items);
        $this->rawState($this->isMultiple() ? $keys : ($keys[0] ?? null));
        parent::hydrateState($hydratedDefaultState, $shouldCallHydrationHooks, $shouldApplyStateCasts, $appliedStateCastPaths);
    }

    protected function dehydrateReferences(mixed $state): mixed
    {
        $values = $this->isMultiple() ? ($state ?? []) : [$state];
        if (! is_array($values)) {
            return null;
        }
        $decoded = array_map(fn ($value) => is_string($value) ? json_decode($value, true, flags: JSON_THROW_ON_ERROR) : null, $values);

        return $this->isMultiple() ? $decoded : ($decoded[0] ?? null);
    }

    protected function search(string $search): array
    {
        $results = [];
        foreach ($this->references as $alias => $definition) {
            foreach ($this->queryFor($alias, true)?->where($definition['label'], 'like', '%' . $search . '%')->limit(50)->get() ?? [] as $record) {
                if (! $this->canSelect($record, $alias)) {
                    continue;
                }
                $key = json_encode(['model' => $alias, 'id' => $record->getKey()], JSON_THROW_ON_ERROR);
                $results[$key] = $this->referenceLabel($record, $alias);
            }
        }

        return $results;
    }

    protected function queryFor(string $alias, bool $forSelection = false): ?Builder
    {
        if (! isset($this->references[$alias]) || ! $this->queryCallback) {
            return null;
        }
        $model = $this->references[$alias]['model'];
        $query = ($this->queryCallback)($model, $alias, $forSelection);

        return $query instanceof Builder ? $query : null;
    }

    protected function canSelect(Model $record, string $alias): bool
    {
        return isset($this->references[$alias]) && $this->authorizationCallback !== null && (bool) ($this->authorizationCallback)($record, $alias);
    }

    protected function labelFor(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $reference = json_decode($value, true);
        $alias = is_array($reference) ? ($reference['model'] ?? null) : null;
        $id = is_array($reference) ? ($reference['id'] ?? null) : null;
        if (! is_string($alias) || (! is_string($id) && ! is_int($id))) {
            return null;
        }
        $record = $this->queryFor($alias)?->find($id);
        if (! $record || ! $this->canSelect($record, $alias)) {
            return null;
        }

        return $this->referenceLabel($record, $alias);
    }

    protected function referenceLabel(Model $record, string $alias): string
    {
        return $this->labelCallback
            ? (string) ($this->labelCallback)($record, $alias)
            : $alias . ': ' . $record->getAttribute($this->references[$alias]['label']);
    }
}

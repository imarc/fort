# Fort

**Safe API filtering & sorting for Laravel**

Whitelist-driven **filters** and **sorts** for Eloquent **index** (list/collection) endpoints. Clients send optional `filters[...]` and `sort` / `sort[]` query parameters; only keys you map in PHP are applied to the query. Everything else is ignored, so arbitrary `WHERE` / `ORDER BY` cannot be injected through the query string.

## Requirements

- PHP ^8.4
- Laravel ^12.0

## Install (consuming application)

Author this package under your app repo (not under `vendor/`, which Composer regenerates). Example layout:

```text
your-app/
  packages/imarc/fort/
  composer.json
```

In the **application** `composer.json`, add a path repository and require the package:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/imarc/fort",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "imarc/fort": "*"
    }
}
```

Then run `composer update imarc/fort`.

There is no service provider: use the trait and base request class directly.

## Architecture

1. **HTTP** — Extend `Imarc\Fort\Http\Requests\FilterableRequest` (or use it inline where appropriate). It validates optional `filters` (array) and `sort` (array of segments). It exposes:
   - `filters()` — validated filter key/value array for the builder.
   - `sorts(?array $defaults = null)` — list of `['key' => string, 'direction' => 'asc'|'desc']` for `applySorts()`. If the request has no `sort` input, defaults come from the argument or from `defaultSorts()` on a subclass.
   - Override `filterMap()` / `sortMap()` on a subclass to return shorthand maps (see below).

2. **Mapping** — `Imarc\Fort\Filters\Filter::map()` and `Imarc\Fort\Sorts\Sort::map()` turn shorthand definitions into concrete `FilterDefinition` / `SortDefinition` instances. Only mapped keys are honored when applying.

3. **Query** — Apply filters and sorts on an Eloquent builder (see **Builder options** below), then call:
   - `applyFilters($request->filters(), $definitions)`
   - `applySorts($request->sorts(), $definitions)`

   Pass `$request->filterMap()` / `$request->sortMap()` when those are defined on your `FilterableRequest` subclass, or pass inline arrays from the controller.

### Builder options

**A. Shared builder (least boilerplate)** — Add `Imarc\Fort\Eloquent\Concerns\HasFilterableQuery` to your model. `Model::query()` then returns `Imarc\Fort\Eloquent\FilterableBuilder`, which already includes `AppliesFiltersAndSorts`. No empty `*Builder` class unless you need custom query methods.

**B. Custom builder** — Extend `FilterableBuilder` (instead of `Illuminate\Database\Eloquent\Builder`) and add domain methods; override `newEloquentBuilder()` on the model to return your subclass. You can still `use AppliesFiltersAndSorts` on a plain `Builder` subclass if you prefer not to extend `FilterableBuilder`, but extending avoids repeating the trait.

**C. Trait only** — `use AppliesFiltersAndSorts` on any `Eloquent\Builder` subclass (the original approach).

## Sort query encoding

- Prefer repeated **`sort[]`** parameters so order is explicit, e.g. `sort[]=rank&sort[]=-due_date`.
- **`-` prefix** on a segment means descending; ascending is the default.
- Optional **`+` prefix** means explicit ascending (stripped before matching the sort key).
- A single legacy **`sort=rank`** string is normalized to a one-element array in `prepareForValidation()`.
- Public sort keys must match `^[a-zA-Z0-9][a-zA-Z0-9_.]*$` after trimming and stripping direction prefixes.

## Filter building blocks

| Factory / type | Role |
|----------------|------|
| `Filter::exact('column')` | Equality on a column; dotted paths become `RelationExactFilter`. |
| `Filter::relationExact('relation', 'column')` | `whereHas` + equality on related column. |
| `Filter::dateRange('column')` | Inclusive `Y-m-d` bounds via `start` / `end` in the filter value array. |
| `Filter::callback(Closure)` | Custom `apply` logic. |
| `Filter::builder(CustomBuilder::class, 'methodName')` | Delegates to a method on your custom builder (0, 1, or 2+ args). |

`FilterDefinition::normalizeScalar()` treats string `'true'`, `'false'`, and `'null'` (case-insensitive) as boolean / null.

## Sort building blocks

| Factory / type | Role |
|----------------|------|
| `Sort::column('column')` or shorthand in `Sort::map()` | `orderBy` on a column. |
| `Sort::callback(Closure)` | Custom ordering. |

## Example (sketch)

**Model using the shared builder (no custom `ItemBuilder` class):**

```php
use Imarc\Fort\Eloquent\Concerns\HasFilterableQuery;
use Imarc\Fort\Filters\Filter;
use Imarc\Fort\Http\Requests\FilterableRequest;
use Illuminate\Database\Eloquent\Model;

final class Item extends Model
{
    use HasFilterableQuery;
}

final class ItemsIndexRequest extends FilterableRequest
{
    public function filterMap(): array
    {
        return [
            'status' => 'status',
            'created_at' => Filter::dateRange('created_at'),
        ];
    }

    public function sortMap(): array
    {
        return ['created_at', 'name'];
    }

    protected function defaultSorts(): array
    {
        return ['-created_at'];
    }
}

// Controller
Item::query()
    ->applyFilters($request->filters(), $request->filterMap())
    ->applySorts($request->sorts(), $request->sortMap())
    ->paginate();
```

**Custom builder** (when you need `Item::query()->forAccount($id)`-style APIs): extend `Imarc\Fort\Eloquent\FilterableBuilder` and return it from `Item::newEloquentBuilder()`.

(Adjust namespaces and add your own validation rules for filter values.)

## Development

```bash
cd packages/imarc/fort
composer install
vendor/bin/phpunit
```

## License

MIT

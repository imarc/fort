# Fort

**Fort provides a safe, declarative layer for applying API filters and sorting to Eloquent queries.**

Instead of turning request parameters directly into database queries, Fort uses explicit mappings to control exactly what can be filtered and sorted, so your API stays predictable, secure, and maintainable.

## Features

* Whitelist-driven filtering and sorting (`Filter::map` / `Sort::map`)
* **`FilterableRequest`** — Laravel `FormRequest` that validates `filters` and `sort` and exposes **`filters()`** / **`sorts()`**; optionally override **`filterMap()`** / **`sortMap()`** / **`defaultSorts()`** in a subclass when you want maps and defaults on the request
* Default sort when the client omits `sort` (`defaultSorts()` on the request, or an argument to `sorts()`)
* String shorthands for column filters and sorts, plus **`Filter::callback`**, **`Sort::callback`**, **`Filter::dateRange`**, relation paths (`relation.column`), and **`Filter::builder`** for custom builder methods
* **`HasFilterableQuery`** — models get a **`FilterableBuilder`** with `applyFilters()` / `applySorts()`
* Multiple sort fields with direction (`-` for descending, optional `+` for ascending)

---

## Why Fort?

Most Laravel apps start with request-driven query logic directly in controllers:

```php
$query->when($request->input('region_id'), fn ($q, $id) =>
    $q->where('region_id', $id)
);
```

This works, but it often leads to:

* duplicated logic across controllers
* tight coupling between request structure and query construction
* inconsistent filtering and sorting behavior between endpoints
* risk of accidentally exposing query behavior you did not intend to support

Fort takes a different approach. Type-hint **`FilterableRequest`** (or a subclass), build a whitelist map, and apply it in one place:

```php
Project::query()
    ->applyFilters($request->filters(), $filters)
    ->applySorts($request->sorts(['-created']), $sorts);
```

With Fort:

* only explicitly allowed filters and sorts are applied
* validation and parsing for `filters` / `sort` stay on the request; maps can live in the controller or on a subclass
* custom behavior uses **`Filter`** / **`Sort`** helpers instead of ad hoc controller branches

---

## How It Works

```text
HTTP (filters[], sort[]) -> FilterableRequest (validate + parse)
                        -> your filter/sort maps
                        -> applyFilters() / applySorts() on the Eloquent builder
```

1. **`FilterableRequest`** validates structure for `filters` and `sort`, normalizes a single `sort=foo` query value into an array, and parses sort segments into `{ key, direction }` entries for **`applySorts()`**.
2. You pass whitelist definitions into **`applyFilters()`** / **`applySorts()`** as arrays, or return them from **`filterMap()`** / **`sortMap()`** when you extend the request (see below).
3. Models using **`HasFilterableQuery`** (or a custom builder extending **`FilterableBuilder`**) get **`applyFilters()`** and **`applySorts()`** on the query builder.

---

## Installation

```bash
composer require imarc/fort
```

---

## Recommended usage

### Type-hint `FilterableRequest`

The usual starting point is to type-hint the base request class and define filter and sort maps next to the query (commonly in the controller). **`FilterableRequest`** only validates and exposes **`filters()`** and **`sorts()`**; it does not require a subclass.

```php
use Illuminate\Database\Eloquent\Builder;
use Imarc\Fort\Filters\Filter;
use Imarc\Fort\Http\Requests\FilterableRequest;
use Imarc\Fort\Sorts\Sort;

public function index(FilterableRequest $request)
{
    $filters = [
        'region' => 'project.region_id',
        'status' => Filter::callback(fn (Builder $q, mixed $v) => $q->where('status', $v)),
    ];

    $sorts = [
        'name' => Sort::column('name'),
        'created' => Sort::callback(fn (Builder $q, string $dir) => $q->orderBy('created_at', $dir)),
    ];

    $projects = Project::query()
        ->applyFilters($request->filters(), $filters)
        ->applySorts($request->sorts(['-created']), $sorts)
        ->get();

    return ProjectResource::collection($projects);
}
```

**Default sort:** pass segments into **`sorts()`** when the query string has no `sort` (e.g. **`$request->sorts(['-created_at'])`**). Pass **`sorts([])`** to apply no ordering when the query omits `sort`.

---

### Extend `FilterableRequest` for heavier endpoints

When maps grow large, you want defaults without repeating them at every call site, or you prefer colocating whitelist definitions with the same form request, subclass **`FilterableRequest`** and override **`filterMap()`** and **`sortMap()`**. Override **`defaultSorts()`** so **`$request->sorts()`** with no arguments applies a fallback when the client omits `sort`.

```php
use Illuminate\Database\Eloquent\Builder;
use Imarc\Fort\Filters\Filter;
use Imarc\Fort\Http\Requests\FilterableRequest;
use Imarc\Fort\Sorts\Sort;

class IndexProjectsRequest extends FilterableRequest
{
    public function filterMap(): array
    {
        return [
            'region' => 'project.region_id',
            'status' => Filter::callback(function (Builder $query, mixed $value): void {
                $query->where('status', $value);
            }),
        ];
    }

    public function sortMap(): array
    {
        return [
            'name' => Sort::column('name'),
            'created' => Sort::callback(function (Builder $query, string $direction): void {
                $query->orderBy('created_at', $direction);
            }),
        ];
    }

    protected function defaultSorts(): array
    {
        return ['-created'];
    }
}
```

```php
public function index(IndexProjectsRequest $request)
{
    $projects = Project::query()
        ->applyFilters($request->filters(), $request->filterMap())
        ->applySorts($request->sorts(), $request->sortMap())
        ->get();

    return ProjectResource::collection($projects);
}
```

---

## Eloquent builder

Use **`Imarc\Fort\Eloquent\Concerns\HasFilterableQuery`** on your model so **`Model::query()`** returns **`FilterableBuilder`**, which includes **`applyFilters()`** and **`applySorts()`**.

If you need extra builder methods, extend **`FilterableBuilder`** and override **`Model::newEloquentBuilder()`** (see the trait docblock).

---

## Request format

### Filtering

```http
GET /projects?filters[region]=1&filters[status]=active
```

Only keys present in your filter map are applied. Nested values (e.g. date ranges) use array-style query parameters as usual for Laravel.

---

### Sorting

A single value is accepted and normalized to an array internally:

```http
GET /projects?sort=name
GET /projects?sort=-created
```

Multiple fields:

```http
GET /projects?sort[]=name&sort[]=-created
```

* `-` prefix → descending  
* `+` prefix → ascending (optional; default is ascending)

Sort keys are validated to a safe pattern (`[a-zA-Z0-9][a-zA-Z0-9_.]*`).

---

## Filter definitions

Maps are **`array<int|string, string|FilterDefinition>`**. Fort normalizes them with **`Filter::map()`**:

| Form | Result |
|------|--------|
| `'column'` or `key => 'column'` | **`ExactFilter`** on that column |
| `'relation.nested.column'` | **`RelationExactFilter`** (`whereHas` style) |
| `Filter::relationExact('relation', 'column')` | Explicit relation filter |
| `Filter::callback(closure)` | Custom `(Builder $query, mixed $value, array $context)` |
| `Filter::dateRange('column')` | Inclusive range; value array with optional `start` / `end` (`Y-m-d`) |
| `Filter::builder(CustomBuilder::class, 'methodName')` | Delegates to a method on your builder when the query matches that class |

**`FilterDefinition`** is the abstract base class; instantiate the concrete types above (or your own subclasses), not `FilterDefinition` directly.

---

## Sort definitions

Maps are **`array<int|string, string|SortDefinition>`**, normalized with **`Sort::map()`**:

| Form | Result |
|------|--------|
| `'column'` or `key => 'column'` | **`ColumnSort`** |
| `Sort::column('column')` | Same, explicit |
| `Sort::callback(closure)` | Custom `(Builder $query, string $direction, array $context)` |

**`SortDefinition`** is abstract; use **`Sort::column()`** / **`Sort::callback()`** or your own subclasses.

---

## `applySorts()` signature

**`applySorts()`** takes only the parsed sort list and the map — there is no third “default” argument. Defaults belong on the request: **`defaultSorts()`** or **`$request->sorts([...])`**.

---

## Whitelisting

Fort ignores filters and sorts that are not in the map. That limits arbitrary query manipulation and keeps public APIs predictable.

---

## Philosophy

Fort is built around one core idea:

**Nothing should affect your query unless you explicitly allow it.**

This makes it useful for public APIs, complex filtering, and teams that want consistent, reviewable rules — starting with a type-hinted **`FilterableRequest`**, and escalating to a subclass when maps and defaults deserve a dedicated form request.

---

## License

MIT

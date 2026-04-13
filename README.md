# Fort

**Fort provides a safe, declarative layer for applying API filters and sorting to Eloquent queries.**

Instead of turning request parameters directly into database queries, Fort uses explicit mappings to control exactly what can be filtered and sorted, so your API stays predictable, secure, and maintainable.

## Features

* Whitelist-driven filtering and sorting
* Clean separation of HTTP input and query logic
* Support for simple column mappings or custom callbacks
* Multiple sort fields with direction (`-` for descending, `+` for ascending)
* Default sorting fallback
* Thin controllers and reusable query logic

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

Fort takes a different approach. It lets you define, in one place, exactly which filters and sorts are allowed, and how they should be applied.

```php
$filters = [
    'region' => 'project.region_id',
];

$sorts = [
    'created' => SortDefinition::column('created_at'),
];

$query
    ->applyFilters($request->filters(), $filters)
    ->applySorts($request->sorts(), $sorts);
```

With Fort:

* only explicitly allowed filters and sorts are applied
* query logic is centralized and reusable
* controllers stay thin and focused
* custom behavior can be encapsulated in definitions instead of scattered across endpoints

---

## How It Works

```text
Request -> filters()/sorts()
        -> Fort maps and definitions
        -> Eloquent query builder
```

Fort sits between HTTP input and your query, translating allowed parameters into safe, predictable query modifications.

---

## Installation

```bash
composer require imarc/fort
```

---

## Usage

### Define filters

```php
$filters = [
    'region' => 'project.region_id',

    'status' => new FilterDefinition(function ($query, $value) {
        $query->where('status', $value);
    }),
];
```

---

### Define sorts

```php
$sorts = [
    'name' => SortDefinition::column('name'),

    'created' => SortDefinition::callback(function ($query, $direction) {
        $query->orderBy('created_at', $direction);
    }),
];
```

---

### Apply to a query

```php
$query = Project::query();

$query
    ->applyFilters($request->filters(), $filters)
    ->applySorts($request->sorts(), $sorts, default: ['-created']);
```

---

## Request Format

### Filtering

```http
GET /projects?filter[region]=1&filter[status]=active
```

Only keys defined in your filter map will be applied.

---

### Sorting

```http
GET /projects?sort=name
GET /projects?sort=-created
GET /projects?sort[]=name&sort[]=-created
```

* `-` prefix = descending
* `+` prefix = ascending (optional)

---

## Concepts

### FilterDefinition

Defines how a filter is applied.

* string → column path (`project.region_id`)
* closure → custom query logic

---

### SortDefinition

Defines how a sort is applied.

* column-based
* callback-based

---

### Whitelisting

Fort ignores any filters or sorts not explicitly defined.

This ensures:

* no arbitrary query conditions
* predictable API behavior
* safer public endpoints

---

## Example Controller

```php
public function index(Request $request)
{
    $filters = [
        'region' => 'project.region_id',
    ];

    $sorts = [
        'created' => SortDefinition::column('created_at'),
    ];

    $projects = Project::query()
        ->applyFilters($request->filters(), $filters)
        ->applySorts($request->sorts(), $sorts, default: ['-created'])
        ->get();

    return ProjectResource::collection($projects);
}
```

---

## Extending

You can encapsulate reusable definitions:

```php
class ProjectFilters
{
    public static function definitions(): array
    {
        return [
            'region' => 'project.region_id',
        ];
    }
}
```

```php
$query->applyFilters($request->filters(), ProjectFilters::definitions());
```

---

## Philosophy

Fort is built around one core idea:

**Nothing should affect your query unless you explicitly allow it.**

This makes it ideal for:

* public APIs
* complex filtering requirements
* teams that value consistency and safety

---

## License

MIT

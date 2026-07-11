# Public UUID Rollout Pattern

Use this pattern for modules that should stop exposing numeric IDs in browser URLs.

## Model

Add `use App\Models\Concerns\HasPublicUuid;` to the model and include `uuid` in `$fillable` when the model uses mass assignment.

```php
use App\Models\Concerns\HasPublicUuid;

class Query extends Model
{
    use HasPublicUuid;
}
```

The trait standardizes:

- generation of UUID values on the `uuid` column
- route model binding by `uuid`
- route generation with `route(..., $model)`

## Migration

Use `Database\Migrations\Concerns\ManagesPublicUuids` inside a migration and run the three steps in order:

```php
use Database\Migrations\Concerns\ManagesPublicUuids;

return new class extends Migration
{
    use ManagesPublicUuids;

    public function up(): void
    {
        $this->addNullablePublicUuidColumn('queries');
        $this->backfillPublicUuids('queries');
        $this->finalizePublicUuidColumn('queries');
    }
};
```

This keeps the integer `id` as the primary key while exposing `uuid` in URLs.

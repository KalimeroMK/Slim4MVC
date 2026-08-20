# Upgrading

## Unreleased — security hardening

This release closes a cross-site data-exposure chain and changes several defaults from
permissive to restrictive. Read the whole page before upgrading: most items require a
configuration change, not a code change.

### PHP 8.4 is now the minimum

`composer.json` previously allowed PHP 8.3, but the code has been calling `mb_trim()`,
`mb_ltrim()`, `mb_rtrim()` and `array_any()` — all added in 8.4 — for a while. Installing
on 8.3 succeeded and then failed at runtime with `Call to undefined function`. The
constraint now matches reality.

### CORS no longer reflects arbitrary origins

Previously `CORS_ORIGINS=*` (the shipped default) combined with a hardcoded
`credentials => true`, and the middleware echoed back whatever `Origin` the request
carried. Any website could therefore make credentialed requests to the API and read the
responses.

Now:

- an origin is echoed back only on an exact allowlist match; an unknown origin gets no
  `Access-Control-Allow-Origin` header at all;
- credentials come from the new `CORS_ALLOW_CREDENTIALS` variable and default to `false`;
- `CORS_ORIGINS=*` forces credentials off and logs a warning.

**Action:** set both variables explicitly.

```env
CORS_ORIGINS=https://app.example.com,https://admin.example.com
CORS_ALLOW_CREDENTIALS=true
```

### API routes no longer accept the session cookie

`Auth::user()` resolved the session before the JWT, and CSRF validation is skipped for
`/api/` paths. A logged-in browser session therefore authenticated API requests, which is
what made the CORS issue exploitable.

`AuthMiddleware` is now a token-only guard: it calls
`setRequest($request, allowSession: false)`, so only a `Bearer` token authenticates. Web
routes are unaffected — `AuthWebMiddleware` and `Auth::user()` still use the session.

**Action:** if you deliberately relied on cookie-authenticated API calls from a
same-origin SPA, either send the JWT instead, or guard those routes with
`AuthWebMiddleware` and add CSRF coverage for them.

### QueryBuilder allowlists are fail-closed

The guards read `if ($config['filterable'] !== [] && ! in_array(...))`, so an empty
allowlist — the default when no config is passed — allowed *every* field through as a
column name. `?include=` had no allowlist at all.

An empty or missing allowlist now permits nothing, and names must look like plain column
references. Two new keys were added:

| Key | Controls |
|-----|----------|
| `includable` | relations loadable via `?include=` |
| `selectable` | columns selectable via `?fields=` |

`default_sort` is developer-supplied and is *not* subject to the `sortable` allowlist,
but it is still validated as an identifier.

**Action:** list what should be filterable, sortable, searchable, includable and
selectable. On models using the `Filterable` trait, the existing `$allowableIncludes`
property is now actually wired up, and `$selectable` is new.

### `CacheInterface::increment()` / `decrement()` take a TTL

Both methods gained a third parameter, `?int $ttl = null`, applied only when the call
creates the counter. `RateLimitMiddleware` needs it to advance a fixed-window counter with
one atomic operation instead of a read-then-write that concurrent requests could all pass.

Two driver bugs were fixed at the same time: `RedisCache::increment()` used to `INCRBY` a
value that `set()` had serialized (which Redis refuses), and `FileCache::increment()`
dropped the TTL, leaving counters that never expired.

**Action:** custom `CacheInterface` implementations must add the parameter.

### JWT tokens must carry an `exp` claim

`JwtDecoder` only checked expiry `if (isset($payload->exp))`, so a token minted without
one was valid forever. Missing, non-numeric and non-object payloads are now rejected.
`JwtService::encode()` always sets `exp`, falling back to `JWT_TTL` (default 3600s).

**Action:** nothing, unless you mint tokens outside `JwtService`/`AdvancedJwtService`.

### `GenericListAction::executeWithFilters()` no longer ignores filters

It used to `unset($filters)` and return an unfiltered page, so callers believed they had
filtered. It now delegates to the repository's `paginateBy()` — provided by
`EloquentRepository` — and throws a `LogicException` when the repository cannot filter.
Passing an empty filter array still just paginates.

**Action:** repositories that do not extend `EloquentRepository` need a
`paginateBy(array $criteria, int $page, int $perPage)` method.

### Filesystem paths resolve through `Paths`

`App\Modules\Core\Infrastructure\Support\Paths` is now the only place that knows how
deep it sits relative to the project root. Every runtime path derives from it, and
`RuntimePathsTest` fails if any file under `app/` walks up to the root by hand again.

The counts had drifted apart, so several paths pointed at directories that did not
exist or sat outside the checkout:

- `db:seed` required `bootstrap/database.php` from one level *above* the project and
  died with a fatal before seeding anything.
- `swagger:generate` defaulted its source and output paths outside the project, so it
  reported `Source directory not found!` and never generated documentation.
- `make:request --model=X` looked for `bootstrap/database.php` under `app/`, silently
  found nothing, and emitted a request class with no validation rules. It now warns and
  still writes the class when no database is reachable.
- `make:request` also built its destination from `app/` and corrected it afterwards with
  a `str_ends_with($projectRoot, '/app')` branch; both are gone.
- `jwt:key:generate` used to try `../.env` as a second candidate, which could write the
  generated secret into a sibling project. Only the project's own `.env` is considered.
- `FileCache` and `CacheManager` defaulted to a `storage/cache/data` directory *beside*
  the checkout. `SendEmailJob` resolved views to `app/Modules/resources/views`, so a
  queued email could never render.

`OptimizedDiscovery::__construct()` takes `?array $scanPaths = null` instead of defaulting
to a constant; passing an explicit array is unchanged. `MakeModelCommand::createModel()`
renamed its third parameter from `$projectRoot` to `$appDir`, which is what it always was.

### `composer test:isolation`

Runs the suite and fails if it wrote anything under `app/` or beside the repository.
`composer check` uses it in place of `composer test`, and CI wraps both the normal and
the randomised runs with it. The script is `scripts/assert-no-source-writes.sh`.

### Smaller changes

- `X-XSS-Protection` is no longer sent. The legacy auditor it enabled is gone from current
  browsers and could itself introduce vulnerabilities; CSP covers this.
- `FileQueue` now defaults to `<project>/storage/queue/jobs.json`. It previously resolved
  to `app/Modules/Core/storage/queue/jobs.json` — inside the source tree, where the file
  escaped `.gitignore` and got committed. Override with `QUEUE_PATH`.
- `DatabaseSeeder` takes an optional output closure and is silent without one, so running
  the test suite no longer prints to stdout. `SeedCommand` and `database/seed/seed.php`
  pass an echoing closure.
- `Tests\TestCase::apiRequest()` and its `assertApiResponse*()` helpers were removed. The
  stub returned a hardcoded `200` with an empty body regardless of input; nothing used it.
- `.env.example` no longer ships a `JWT_SECRET` value. It was 19 characters, below the
  32-character minimum `EnvironmentValidator` enforces, so it never worked anyway.

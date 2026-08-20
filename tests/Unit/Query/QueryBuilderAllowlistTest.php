<?php

declare(strict_types=1);

namespace Tests\Unit\Query;

use App\Modules\Core\Infrastructure\Query\QueryBuilder;
use App\Modules\Role\Infrastructure\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ServerRequestInterface;
use Tests\TestCase;

/**
 * Plain Eloquent model so these assertions observe QueryBuilder only, without
 * auto-eager-loading global scopes interfering with getEagerLoads().
 */
final class QueryBuilderTestUser extends Model
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }
}

/**
 * Allowlists are fail-closed: a name absent from the config must never reach the
 * query builder as a column or relation.
 */
#[CoversClass(QueryBuilder::class)]
final class QueryBuilderAllowlistTest extends TestCase
{
    // ---------------------------------------------------------------- filters

    public function test_filter_is_ignored_without_config(): void
    {
        $builder = $this->apply(['filter' => ['name' => 'test']], null);

        $this->assertStringNotContainsString('where', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
    }

    public function test_filter_is_ignored_with_empty_allowlist(): void
    {
        $builder = $this->apply(['filter' => ['name' => 'test']], ['filterable' => []]);

        $this->assertStringNotContainsString('where', $builder->toSql());
    }

    public function test_allowlisted_filter_is_applied(): void
    {
        $builder = $this->apply(
            ['filter' => ['name' => 'test']],
            ['filterable' => ['name', 'email']]
        );

        $this->assertStringContainsString('"name" = ?', $builder->toSql());
        $this->assertSame(['test'], $builder->getBindings());
    }

    public function test_filter_outside_the_allowlist_is_dropped_while_others_apply(): void
    {
        $builder = $this->apply(
            ['filter' => ['name' => 'test', 'password' => 'secret']],
            ['filterable' => ['name']]
        );

        $this->assertStringContainsString('"name" = ?', $builder->toSql());
        $this->assertStringNotContainsString('password', $builder->toSql());
        $this->assertSame(['test'], $builder->getBindings());
    }

    public function test_sql_shaped_filter_key_is_rejected(): void
    {
        $builder = $this->apply(
            ['filter' => ['name") or 1=1 --' => 'x']],
            ['filterable' => ['name']]
        );

        $this->assertStringNotContainsString('or 1=1', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
    }

    public function test_allowlist_entry_that_is_not_a_plain_identifier_is_still_rejected(): void
    {
        // Defence in depth: even a mis-configured allowlist cannot inject.
        $builder = $this->apply(
            ['filter' => ['name); drop table users; --' => 'x']],
            ['filterable' => ['name); drop table users; --']]
        );

        $this->assertStringNotContainsString('drop table', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
    }

    public function test_table_qualified_column_is_accepted(): void
    {
        $builder = $this->apply(
            ['filter' => ['users.email' => 'a@b.c']],
            ['filterable' => ['users.email']]
        );

        $this->assertStringContainsString('"users"."email" = ?', $builder->toSql());
    }

    public function test_filter_operators_are_applied_for_allowlisted_columns(): void
    {
        $builder = $this->apply(
            ['filter' => ['name' => 'like:jo']],
            ['filterable' => ['name']]
        );

        $this->assertStringContainsString('"name" like ?', $builder->toSql());
        $this->assertSame(['%jo%'], $builder->getBindings());
    }

    // ----------------------------------------------------------------- ranges

    public function test_range_is_ignored_when_column_is_not_filterable(): void
    {
        $builder = $this->apply(['range' => ['id' => '1,10']], ['filterable' => ['name']]);

        $this->assertStringNotContainsString('where', $builder->toSql());
    }

    public function test_allowlisted_range_is_applied(): void
    {
        $builder = $this->apply(['range' => ['id' => '1,10']], ['filterable' => ['id']]);

        $sql = $builder->toSql();
        $this->assertStringContainsString('"id" >= ?', $sql);
        $this->assertStringContainsString('"id" <= ?', $sql);
        $this->assertSame([1, 10], $builder->getBindings());
    }

    // ------------------------------------------------------------------ sorts

    public function test_sort_is_ignored_when_not_sortable(): void
    {
        $builder = $this->apply(['sort' => 'password'], ['sortable' => ['name']]);

        $this->assertStringNotContainsString('order by', $builder->toSql());
    }

    public function test_allowlisted_sort_is_applied_in_both_directions(): void
    {
        $asc = $this->apply(['sort' => 'name'], ['sortable' => ['name']]);
        $desc = $this->apply(['sort' => '-name'], ['sortable' => ['name']]);

        $this->assertStringContainsString('order by "name" asc', $asc->toSql());
        $this->assertStringContainsString('order by "name" desc', $desc->toSql());
    }

    public function test_default_sort_applies_without_being_in_the_sortable_allowlist(): void
    {
        // default_sort is developer-supplied, not user-supplied.
        $builder = $this->apply([], ['sortable' => [], 'default_sort' => ['created_at' => 'desc']]);

        $this->assertStringContainsString('order by "created_at" desc', $builder->toSql());
    }

    public function test_default_sort_applies_when_the_requested_sort_was_rejected(): void
    {
        $builder = $this->apply(
            ['sort' => 'password'],
            ['sortable' => ['name'], 'default_sort' => ['id' => 'asc']]
        );

        $this->assertStringContainsString('order by "id" asc', $builder->toSql());
        $this->assertStringNotContainsString('password', $builder->toSql());
    }

    public function test_requested_sort_suppresses_the_default_sort(): void
    {
        $builder = $this->apply(
            ['sort' => 'name'],
            ['sortable' => ['name'], 'default_sort' => ['id' => 'asc']]
        );

        $this->assertStringContainsString('order by "name" asc', $builder->toSql());
        $this->assertStringNotContainsString('"id" asc', $builder->toSql());
    }

    public function test_default_sort_with_an_unsafe_column_is_rejected(): void
    {
        $builder = $this->apply([], ['default_sort' => ['id; drop table users' => 'asc']]);

        $this->assertStringNotContainsString('drop table', $builder->toSql());
        $this->assertStringNotContainsString('order by', $builder->toSql());
    }

    public function test_default_sort_with_an_invalid_direction_is_rejected(): void
    {
        $builder = $this->apply([], ['default_sort' => ['id' => 'asc; drop table users']]);

        $this->assertStringNotContainsString('order by', $builder->toSql());
    }

    // ----------------------------------------------------------------- search

    public function test_search_does_nothing_without_searchable_columns(): void
    {
        $builder = $this->apply(['search' => 'jo'], ['searchable' => []]);

        $this->assertStringNotContainsString('like', $builder->toSql());
    }

    public function test_search_spans_the_configured_columns(): void
    {
        $builder = $this->apply(['search' => 'jo'], ['searchable' => ['name', 'email']]);

        $sql = $builder->toSql();
        $this->assertStringContainsString('"name" like ?', $sql);
        $this->assertStringContainsString('or "email" like ?', $sql);
        $this->assertSame(['%jo%', '%jo%'], $builder->getBindings());
    }

    public function test_unsafe_searchable_entry_is_skipped(): void
    {
        $builder = $this->apply(['search' => 'jo'], ['searchable' => ['name', 'email) --']]);

        $this->assertStringNotContainsString('--', $builder->toSql());
        $this->assertSame(['%jo%'], $builder->getBindings());
    }

    // --------------------------------------------------------------- includes

    public function test_include_is_ignored_without_an_includable_allowlist(): void
    {
        $builder = $this->apply(['include' => 'roles'], ['filterable' => ['name']]);

        $this->assertSame([], array_keys($builder->getEagerLoads()));
    }

    public function test_allowlisted_include_is_eager_loaded(): void
    {
        $builder = $this->apply(['include' => 'roles'], ['includable' => ['roles']]);

        $this->assertContains('roles', array_keys($builder->getEagerLoads()));
    }

    public function test_nested_include_must_be_listed_explicitly(): void
    {
        $rejected = $this->apply(['include' => 'roles.permissions'], ['includable' => ['roles']]);
        $allowed = $this->apply(['include' => 'roles.permissions'], ['includable' => ['roles.permissions']]);

        $this->assertSame([], array_keys($rejected->getEagerLoads()));
        $this->assertContains('roles.permissions', array_keys($allowed->getEagerLoads()));
    }

    // ----------------------------------------------------------------- fields

    public function test_fields_are_ignored_without_a_selectable_allowlist(): void
    {
        $builder = $this->apply(['fields' => 'name,password'], ['filterable' => ['name']]);

        $this->assertStringContainsString('select * from', $builder->toSql());
    }

    public function test_only_selectable_fields_reach_the_select_clause(): void
    {
        $builder = $this->apply(['fields' => 'name,password'], ['selectable' => ['name', 'email']]);

        $sql = $builder->toSql();
        $this->assertStringContainsString('select "name" from', $sql);
        $this->assertStringNotContainsString('password', $sql);
    }

    /**
     * @param  array<string, mixed>  $queryParams
     * @param  array<string, mixed>|null  $config
     * @return Builder<QueryBuilderTestUser>
     */
    private function apply(array $queryParams, ?array $config): Builder
    {
        $queryBuilder = new QueryBuilder($this->createRequest($queryParams), $config);

        return $queryBuilder->apply(QueryBuilderTestUser::query());
    }

    /**
     * @param  array<string, mixed>  $queryParams
     */
    private function createRequest(array $queryParams): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);

        return $request;
    }
}

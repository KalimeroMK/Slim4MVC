<?php

declare(strict_types=1);

namespace Tests\Unit\Query;

use App\Modules\Core\Infrastructure\Query\QueryBuilder;
use App\Modules\Core\Infrastructure\Query\QueryParser;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class QueryBuilderTest extends TestCase
{
    public function test_query_parser_parses_filters(): void
    {
        $request = $this->createRequest([
            'filter' => [
                'status' => 'active',
                'role' => 'admin',
            ],
        ]);

        $parser = new QueryParser($request);

        $this->assertEquals(['status' => 'active', 'role' => 'admin'], $parser->filters());
    }

    public function test_query_parser_parses_sort_ascending(): void
    {
        $request = $this->createRequest(['sort' => 'name']);
        $parser = new QueryParser($request);

        $this->assertEquals(['name' => 'asc'], $parser->sorts());
    }

    public function test_query_parser_parses_sort_descending(): void
    {
        $request = $this->createRequest(['sort' => '-created_at']);
        $parser = new QueryParser($request);

        $this->assertEquals(['created_at' => 'desc'], $parser->sorts());
    }

    public function test_query_parser_parses_multiple_sorts(): void
    {
        $request = $this->createRequest(['sort' => '-created_at,name']);
        $parser = new QueryParser($request);

        $this->assertEquals(['created_at' => 'desc', 'name' => 'asc'], $parser->sorts());
    }

    public function test_query_parser_parses_search(): void
    {
        $request = $this->createRequest(['search' => 'john doe']);
        $parser = new QueryParser($request);

        $this->assertEquals('john doe', $parser->search());
    }

    public function test_query_parser_returns_null_for_empty_search(): void
    {
        $request = $this->createRequest(['search' => '']);
        $parser = new QueryParser($request);

        $this->assertNull($parser->search());
    }

    public function test_query_parser_parses_fields(): void
    {
        $request = $this->createRequest(['fields' => 'id,name,email']);
        $parser = new QueryParser($request);

        $this->assertEquals(['id', 'name', 'email'], $parser->fields());
    }

    public function test_query_parser_parses_includes(): void
    {
        $request = $this->createRequest(['include' => 'posts,comments']);
        $parser = new QueryParser($request);

        $this->assertEquals(['posts', 'comments'], $parser->includes());
    }

    public function test_query_parser_parses_ranges(): void
    {
        $request = $this->createRequest([
            'range' => [
                'price' => '10,100',
                'age' => '18,65',
            ],
        ]);
        $parser = new QueryParser($request);

        $ranges = $parser->ranges();

        $this->assertEquals(['min' => 10, 'max' => 100], $ranges['price']);
        $this->assertEquals(['min' => 18, 'max' => 65], $ranges['age']);
    }

    public function test_query_parser_pagination_defaults(): void
    {
        $request = $this->createRequest([]);
        $parser = new QueryParser($request);

        $pagination = $parser->pagination();

        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(15, $pagination['perPage']);
    }

    public function test_query_parser_pagination_custom(): void
    {
        $request = $this->createRequest(['page' => '3', 'per_page' => '50']);
        $parser = new QueryParser($request);

        $pagination = $parser->pagination();

        $this->assertEquals(3, $pagination['page']);
        $this->assertEquals(50, $pagination['perPage']);
    }

    public function test_query_parser_pagination_limits_per_page(): void
    {
        $request = $this->createRequest(['per_page' => '200']);
        $parser = new QueryParser($request);

        $this->assertEquals(100, $parser->perPage(15, 100));
    }

    public function test_query_parser_returns_raw_filter_values(): void
    {
        // QueryParser returns raw values - casting happens in QueryBuilder
        $request = $this->createRequest([
            'filter' => [
                'active' => 'true',
                'count' => '42',
            ],
        ]);
        $parser = new QueryParser($request);
        $filters = $parser->filters();

        $this->assertSame('true', $filters['active']);
        $this->assertSame('42', $filters['count']);
    }

    public function test_query_parser_casts_range_values(): void
    {
        // Range values are cast to appropriate types
        $request = $this->createRequest([
            'range' => [
                'quantity' => '10,100',
                'price' => '9.99,99.99',
            ],
        ]);
        $parser = new QueryParser($request);
        $ranges = $parser->ranges();

        $this->assertSame(10, $ranges['quantity']['min']);
        $this->assertSame(100, $ranges['quantity']['max']);
        $this->assertSame(9.99, $ranges['price']['min']);
        $this->assertSame(99.99, $ranges['price']['max']);
    }

    public function test_query_parser_returns_raw_params(): void
    {
        $params = ['filter' => ['name' => 'test'], 'sort' => '-id'];
        $request = $this->createRequest($params);
        $parser = new QueryParser($request);

        $this->assertEquals($params, $parser->raw());
    }

    public function test_query_parser_is_empty(): void
    {
        $request = $this->createRequest([]);
        $parser = new QueryParser($request);

        $this->assertTrue($parser->isEmpty());
    }

    public function test_query_builder_uses_config(): void
    {
        $request = $this->createRequest(['filter' => ['name' => 'test']]);
        $config = [
            'filterable' => ['name', 'email'],
            'sortable' => ['created_at'],
            'searchable' => ['name', 'email'],
        ];

        $builder = new QueryBuilder($request, $config);

        $this->assertInstanceOf(QueryBuilder::class, $builder);
    }

    public function test_query_helper_functions_exist(): void
    {
        $request = $this->createRequest([]);

        $this->assertInstanceOf(QueryParser::class, query_parser($request));
        $this->assertInstanceOf(QueryBuilder::class, query_builder($request));
    }

    private function createRequest(array $queryParams): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);

        return $request;
    }
}

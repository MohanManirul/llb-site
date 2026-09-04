<?php

namespace Tests\Feature;

use App\Facades\ApiResponse;
use App\Http\Resources\Company\CompanyResource;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_consistent_success_response(): void
    {
        $response = ApiResponse::respondWithSuccess(['id' => 1], 'Loaded.');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Loaded.',
            'result' => ['id' => 1],
        ], $response->getData(true));
    }

    public function test_it_returns_created_data_in_the_result_key(): void
    {
        $response = ApiResponse::respondCreated(['id' => 1]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Created successfully.',
            'result' => ['id' => 1],
        ], $response->getData(true));
    }

    public function test_it_returns_a_consistent_error_response(): void
    {
        $response = ApiResponse::respondNotFound('Record not found.');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Record not found.',
            'result' => null,
        ], $response->getData(true));
    }

    public function test_it_returns_validation_errors(): void
    {
        $validator = Validator::make([], ['name' => ['required']]);
        $response = ApiResponse::respondValidationErrors(new ValidationException($validator));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
        $this->assertArrayHasKey('name', $response->getData(true)['errors']);
    }

    public function test_it_returns_an_empty_no_content_response(): void
    {
        $response = ApiResponse::respondNoContent();

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function test_it_puts_a_single_resource_straight_into_the_result_key(): void
    {
        $company = $this->makeCompany('Acme Corp');

        $response = ApiResponse::respondWithResource(
            new CompanyResource($company),
            'Company retrieved successfully.',
        );

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Company retrieved successfully.', $payload['message']);
        $this->assertSame($company->id, $payload['result']['id']);
        $this->assertSame('Acme Corp', $payload['result']['name']);
    }

    public function test_a_resource_collection_carries_simple_pagination_links_and_meta(): void
    {
        foreach (['Acme Corp', 'Globex', 'Initech'] as $name) {
            $this->makeCompany($name);
        }

        $paginator = Company::query()->orderBy('id')->simplePaginate(2);

        $response = ApiResponse::respondWithResourceCollection(
            CompanyResource::collection($paginator),
            'Companies retrieved successfully.',
        );

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertCount(2, $payload['result']['data']);

        // simplePaginate knows there is a next page but never counts the total.
        $this->assertNull($payload['result']['links']['prev']);
        $this->assertNotNull($payload['result']['links']['next']);
        $this->assertSame(1, $payload['result']['meta']['current_page']);
        $this->assertSame(2, $payload['result']['meta']['per_page']);
        $this->assertSame(1, $payload['result']['meta']['from']);
        $this->assertSame(2, $payload['result']['meta']['to']);
        $this->assertArrayNotHasKey('total', $payload['result']['meta']);
        $this->assertArrayNotHasKey('last_page', $payload['result']['meta']);
    }

    private function makeCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'code' => str_replace(' ', '', $name),
            'email' => str_replace(' ', '', strtolower($name)).'@example.com',
            'is_active' => true,
        ]);
    }
}

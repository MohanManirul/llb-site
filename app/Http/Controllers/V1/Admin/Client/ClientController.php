<?php

namespace App\Http\Controllers\V1\Admin\Client;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Client\ClientRequest;
use App\Http\Requests\V1\Admin\Client\ImportClientsRequest;
use App\Http\Requests\V1\Admin\Client\IndexClientRequest;
use App\Http\Resources\Client\ClientResource;
use App\Jobs\ImportClientsChunkJob;
use App\Models\Client;
use App\Services\Client\ClientImportService;
use App\Services\Client\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view clients', only: ['index', 'show']),
            new Middleware('permission:create clients', only: ['store', 'import']),
            new Middleware('permission:edit clients', only: ['update']),
            new Middleware('permission:delete clients', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly ClientService $clientService,
        private readonly ClientImportService $clientImportService,
    ) {}

    public function index(IndexClientRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            ClientResource::collection($this->clientService->paginate($request->filters())),
            'Clients retrieved successfully.',
        );
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $client = DB::transaction(function () use ($request) {
            $client = $this->clientService->create(
                $request->validated(),
                $this->storeFile($request, 'image'),
            );

            activity()->performedOn($client)->log('Client created.');

            return $client;
        });

        return ApiResponse::respondWithResource(
            new ClientResource($client),
            'Client created successfully.',
            201,
        );
    }

    public function show(Client $client): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new ClientResource($client),
            'Client retrieved successfully.',
        );
    }

    public function update(ClientRequest $request, Client $client): JsonResponse
    {
        $client = DB::transaction(function () use ($request, $client) {
            $client = $this->clientService->update(
                $client,
                $request->validated(),
                $this->storeFile($request, 'image', $client->image),
            );

            activity()->performedOn($client)->log('Client updated.');

            return $client;
        });

        return ApiResponse::respondWithResource(
            new ClientResource($client),
            'Client updated successfully.',
        );
    }

    public function destroy(Client $client): JsonResponse
    {
        DB::transaction(function () use ($client) {
            activity()->performedOn($client)->log('Client deleted.');

            $this->clientService->delete($client);
        });

        return ApiResponse::respondSuccess('Client deleted successfully.');
    }

    public function import(ImportClientsRequest $request): JsonResponse
    {
        $file = $request->file('file');

        $rows = DB::transaction(function () use ($file) {
            $batch = $this->clientImportService->readRows($file);

            dispatch(new ImportClientsChunkJob($batch['rows'], $batch['columns']))->afterCommit();

            $count = count($batch['rows']);

            activity()->log("Clients CSV queued for import: {$count} row(s) from {$file->getClientOriginalName()}.");

            return $count;
        });

        return ApiResponse::respondWithSuccess(
            data: ['rows' => $rows],
            message: "{$rows} row(s) queued for import.",
        );
    }
}

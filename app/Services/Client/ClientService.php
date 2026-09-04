<?php

namespace App\Services\Client;

use App\DTOs\FilterData;
use App\Models\Client;
use App\Utilities\Asset;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

final class ClientService
{
    public function __construct() {}

    /**
     * @return Paginator<int, Client>
     */
    public function paginate(FilterData $filters): Paginator
    {
        return Client::query()
            ->searchable($filters->search, ['name', 'email', 'phone'])
            ->filterable($filters->only(['is_active']))
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->simplePaginate($filters->perPage);
    }

    public function create(array $data, ?string $imagePath = null): Client
    {
        $data['image'] = $imagePath;
        $phone = formatPhoneNumber($data['phone'] ?? null);
        $data['phone'] = $phone;

        if (empty($data['password'] ?? null)) {
            unset($data['password']);
        }

        DB::beginTransaction();
        try {
            $client = Client::create($data);

            DB::commit();

            return $client;
        } catch (\Throwable $e) {
            DB::rollBack();
            Asset::removeFile($imagePath);
            Asset::removeFile(getThumbnailPath($imagePath));

            throw $e;
        }
    }

    public function update(Client $client, array $data, ?string $imagePath = null): Client
    {
        $oldImage = $client->image;
        $data['image'] = $imagePath;

        if (empty($data['password'] ?? null)) {
            unset($data['password']);
        }

        DB::beginTransaction();
        try {
            $client->update($data);

            DB::commit();

            return $client->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($imagePath !== $oldImage) {
                Asset::removeFile($imagePath);
                Asset::removeFile(getThumbnailPath($imagePath));
            }

            throw $e;
        }
    }

    public function delete(Client $client): void
    {
        DB::transaction(function () use ($client) {
            $client->tokens()->delete();
            $client->delete();
        });
    }
}

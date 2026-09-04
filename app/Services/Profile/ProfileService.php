<?php

namespace App\Services\Profile;

use App\Models\Client;
use App\Models\User;
use App\Utilities\Asset;
use Illuminate\Support\Facades\DB;

final class ProfileService
{
    public function update(User|Client $user, array $data, ?string $imagePath = null): User|Client
    {
        $oldImage = $user->image;

        try {
            return DB::transaction(function () use ($user, $data, $imagePath) {
                $user->name = $data['name'];
                $user->email = $data['email'];

                if (! empty($data['password'])) {
                    $user->password = $data['password'];
                }

                $user->image = $imagePath;

                $user->save();

                return $user;
            });
        } catch (\Throwable $e) {
            if ($imagePath !== $oldImage) {
                Asset::removeFile($imagePath);
                Asset::removeFile(getThumbnailPath($imagePath));
            }

            throw $e;
        }
    }
}

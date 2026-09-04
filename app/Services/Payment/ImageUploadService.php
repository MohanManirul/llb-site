<?php

namespace App\Services\Payment;

use App\Utilities\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    public function uploadProofImage(UploadedFile $file): string
    {
        $path = Asset::generateUploadPath($file->getClientOriginalName(), 'payment-proofs');

        $disk = Storage::disk(config('filesystems.default'));
        $disk->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        return $path;
    }

    public function deleteProofImage(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return Asset::removeFile($path);
    }
}

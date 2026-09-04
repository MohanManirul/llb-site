<?php

namespace App\Traits;

use App\Utilities\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\ImageManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasFileUploadSupport
{
    public function storeFile(Request $request, string $name, ?string $old = null, bool $deleteOldIfEmpty = false): ?string
    {
        if (! $request->$name) {
            if ($deleteOldIfEmpty) {
                Asset::removeFile($old);
                Asset::removeFile(getThumbnailPath($old));

                return null;
            }

            return $old;
        }

        if ($request->hasFile($name)) {
            $file = $request->file($name);
            $fileName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $uploadDirectory = Str::plural(Str::before($mimeType, '/'));
            Asset::removeFile($old);
            Asset::removeFile(getThumbnailPath($old));

            $filePath = $file->storeAs('', Asset::generateUploadPath($fileName, $uploadDirectory));

            if (Str::startsWith($mimeType, 'image/')) {
                $this->generateThumbnail($file, $filePath);
            }

            return $filePath;
        }

        return $old;
    }

    public function generateThumbnail(UploadedFile $file, string $filePath): bool
    {
        try {
            $thumbnail = app(ImageManager::class)
                ->fromUpload($file)
                ->resize(120, 120)
                ->toPng();

            $thumbnailPath = Asset::buildThumbnailPath($filePath);
            Storage::disk(config('filesystems.default'))->put($thumbnailPath, $thumbnail->toBytes());

            return true;
        } catch (\Throwable $e) {
            Log::error('Error generating thumbnail: '.$e->getMessage());

            return false;
        }
    }
}

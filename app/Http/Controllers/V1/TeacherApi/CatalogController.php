<?php

namespace App\Http\Controllers\V1\TeacherApi;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function subjects(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $subjects = Subject::query()
            ->where('is_active', true)
            ->searchable($search ?: null, ['name_bn', 'name_en', 'code'])
            ->orderBy('name_en')
            ->limit(50)
            ->get()
            ->map(fn (Subject $subject) => [
                'value' => $subject->id,
                'label' => $subject->name_en ?? $subject->name_bn,
                'label_bn' => $subject->name_bn,
            ]);

        return ApiResponse::respondWithSuccess($subjects, 'Subjects retrieved successfully.');
    }
}

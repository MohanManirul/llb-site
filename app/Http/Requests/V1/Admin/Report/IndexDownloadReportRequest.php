<?php

namespace App\Http\Requests\V1\Admin\Report;

use App\Enums\MaterialType;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexDownloadReportRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['download_count', 'view_count', 'period_downloads', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'download_count';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(MaterialType::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }
}

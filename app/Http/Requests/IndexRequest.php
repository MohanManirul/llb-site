<?php

namespace App\Http\Requests;

use App\DTOs\FilterData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

abstract class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return ['id'];
    }

    protected function defaultSort(): string
    {
        return 'id';
    }

    protected function defaultDirection(): string
    {
        return 'desc';
    }

    protected function defaultPerPage(): int
    {
        return 10;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', Rule::in($this->allowedSorts())],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ], $this->filterRules());
    }

    public function filters(): FilterData
    {
        return new FilterData(
            search: $this->validated('search'),
            sortBy: $this->validated('sort') ?? $this->defaultSort(),
            sortDir: $this->validated('direction') ?? $this->defaultDirection(),
            page: (int) ($this->validated('page') ?? 1),
            perPage: (int) ($this->validated('per_page') ?? $this->defaultPerPage()),
            filters: Arr::except($this->validated(), [
                'search', 'page', 'per_page', 'sort', 'direction',
            ]),
        );
    }
}

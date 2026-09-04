<?php

namespace App\Http\Controllers\V1\Admin\Question;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Question\ImportQuestionRequest;
use App\Services\Question\QuestionImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\Response;

class QuestionImportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:create questions', only: ['template', 'store']),
        ];
    }

    public function __construct(
        private readonly QuestionImportService $questionImportService,
    ) {}

    public function template(): Response
    {
        return response($this->questionImportService->template(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="question-import-template.csv"',
        ]);
    }

    public function store(ImportQuestionRequest $request): JsonResponse
    {
        $report = $this->questionImportService->import(
            $request->file('file'),
            $request->user()->id,
        );

        if ($report['imported'] > 0) {
            activity()->log("Imported {$report['imported']} questions from CSV.");
        }

        return ApiResponse::respondWithSuccess($report, 'Import processed.');
    }
}

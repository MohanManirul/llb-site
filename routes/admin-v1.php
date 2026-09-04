<?php

use App\Http\Controllers\V1\Admin\Academic\AcademicSessionController;
use App\Http\Controllers\V1\Admin\Academic\ProgramController;
use App\Http\Controllers\V1\Admin\Academic\ProgramLevelController;
use App\Http\Controllers\V1\Admin\Academic\SubjectController;
use App\Http\Controllers\V1\Admin\Access\AccessController;
use App\Http\Controllers\V1\Admin\ActivityLog\ActivityLogController;
use App\Http\Controllers\V1\Admin\Auth\AuthController;
use App\Http\Controllers\V1\Admin\Dashboard\DashboardController;
use App\Http\Controllers\V1\Admin\Dashboard\DashboardReportController;
use App\Http\Controllers\V1\Admin\ModelTest\ModelTestController;
use App\Http\Controllers\V1\Admin\ModelTest\ModelTestQuestionController;
use App\Http\Controllers\V1\Admin\Notice\NoticeController;
use App\Http\Controllers\V1\Admin\Notification\NotificationController;
use App\Http\Controllers\V1\Admin\Profile\ProfileController;
use App\Http\Controllers\V1\Admin\Question\QuestionController;
use App\Http\Controllers\V1\Admin\Question\QuestionImportController;
use App\Http\Controllers\V1\Admin\Report\AnalyticsReportController;
use App\Http\Controllers\V1\Admin\Role\RoleController;
use App\Http\Controllers\V1\Admin\Student\StudentController;
use App\Http\Controllers\V1\Admin\StudyMaterial\MaterialFileController;
use App\Http\Controllers\V1\Admin\StudyMaterial\StudyMaterialController;
use App\Http\Controllers\V1\Admin\User\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/report', [DashboardReportController::class, 'index'])
            ->name('dashboard.report');

        Route::controller(AuthController::class)->group(function () {
            Route::get('/me', 'me');
            Route::post('/logout', 'logout');
        });

        // User picker — must stay above apiResource('users').
        Route::controller(AnalyticsReportController::class)
            ->prefix('reports')
            ->name('reports.')
            ->group(function () {
                Route::get('live', 'live')->name('live');
                Route::get('downloads', 'downloads')->name('downloads');
                Route::get('downloads/{studyMaterial}/files', 'materialFiles')->name('downloads.files');
            });

        Route::get('users/search', [UserController::class, 'search'])
            ->name('users.search');

        // Login provisioning.
        Route::controller(AccessController::class)->group(function () {
            Route::post('staff', 'storeStaff')->name('staff.store');

            Route::get('permissions', 'permissions')->name('permissions.index');
        });

        // Users & roles — option lists must stay above the apiResource.
        Route::get('users/roles', [UserController::class, 'roleOptions'])
            ->name('users.roles');
        Route::apiResource('users', UserController::class);

        Route::get('roles/permission-groups', [RoleController::class, 'permissionGroups'])
            ->name('roles.permission-groups');
        Route::apiResource('roles', RoleController::class);

        // Academic structure — option lists must stay above their apiResource.
        Route::get('programs/options', [ProgramController::class, 'options'])
            ->name('programs.options');
        Route::apiResource('programs', ProgramController::class);

        Route::apiResource('program-levels', ProgramLevelController::class)
            ->parameters(['program-levels' => 'programLevel'])
            ->only(['store', 'update', 'destroy']);

        Route::get('academic-sessions/options', [AcademicSessionController::class, 'options'])
            ->name('academic-sessions.options');
        Route::patch('academic-sessions/{academicSession}/current', [AcademicSessionController::class, 'markCurrent'])
            ->name('academic-sessions.current');
        Route::apiResource('academic-sessions', AcademicSessionController::class)
            ->parameters(['academic-sessions' => 'academicSession']);

        Route::get('subjects/options', [SubjectController::class, 'options'])
            ->name('subjects.options');
        Route::apiResource('subjects', SubjectController::class);

        // Study materials — literal segments must stay above the apiResource.
        Route::get('study-materials/filters', [StudyMaterialController::class, 'filterOptions'])
            ->name('study-materials.filters');
        Route::patch('study-materials/{studyMaterial}/publish', [StudyMaterialController::class, 'publish'])
            ->name('study-materials.publish');
        Route::patch('study-materials/{studyMaterial}/unpublish', [StudyMaterialController::class, 'unpublish'])
            ->name('study-materials.unpublish');

        Route::post('study-materials/{studyMaterial}/files', [MaterialFileController::class, 'store'])
            ->name('study-materials.files.store');
        Route::patch('study-materials/{studyMaterial}/files/reorder', [MaterialFileController::class, 'reorder'])
            ->name('study-materials.files.reorder');
        Route::get('study-materials/{studyMaterial}/files/{file}/preview', [MaterialFileController::class, 'preview'])
            ->scopeBindings()
            ->name('study-materials.files.preview');
        Route::delete('study-materials/{studyMaterial}/files/{file}', [MaterialFileController::class, 'destroy'])
            ->scopeBindings()
            ->name('study-materials.files.destroy');

        Route::apiResource('study-materials', StudyMaterialController::class)
            ->parameters(['study-materials' => 'studyMaterial']);

        // Notices — literal segments must stay above the apiResource.
        Route::get('notices/filters', [NoticeController::class, 'filterOptions'])
            ->name('notices.filters');
        Route::get('notices/{notice}/attachment', [NoticeController::class, 'attachment'])
            ->name('notices.attachment');
        Route::patch('notices/{notice}/publish', [NoticeController::class, 'publish'])
            ->name('notices.publish');
        Route::patch('notices/{notice}/unpublish', [NoticeController::class, 'unpublish'])
            ->name('notices.unpublish');
        Route::apiResource('notices', NoticeController::class);

        // Question bank — literal segments must stay above the apiResource.
        Route::get('questions/filters', [QuestionController::class, 'filterOptions'])
            ->name('questions.filters');
        Route::get('questions/import/template', [QuestionImportController::class, 'template'])
            ->name('questions.import.template');
        Route::post('questions/import', [QuestionImportController::class, 'store'])
            ->name('questions.import');
        Route::patch('questions/{question}/publish', [QuestionController::class, 'publish'])
            ->name('questions.publish');
        Route::patch('questions/{question}/unpublish', [QuestionController::class, 'unpublish'])
            ->name('questions.unpublish');
        Route::apiResource('questions', QuestionController::class);

        // Model tests — literal segments must stay above the apiResource.
        Route::get('model-tests/filters', [ModelTestController::class, 'filterOptions'])
            ->name('model-tests.filters');
        Route::patch('model-tests/{modelTest}/publish', [ModelTestController::class, 'publish'])
            ->name('model-tests.publish');
        Route::patch('model-tests/{modelTest}/unpublish', [ModelTestController::class, 'unpublish'])
            ->name('model-tests.unpublish');
        Route::post('model-tests/{modelTest}/questions', [ModelTestQuestionController::class, 'store'])
            ->name('model-tests.questions.store');
        Route::patch('model-tests/{modelTest}/questions/reorder', [ModelTestQuestionController::class, 'reorder'])
            ->name('model-tests.questions.reorder');
        Route::delete('model-tests/{modelTest}/questions/{question}', [ModelTestQuestionController::class, 'destroy'])
            ->name('model-tests.questions.destroy');
        Route::apiResource('model-tests', ModelTestController::class)
            ->parameters(['model-tests' => 'modelTest']);

        // Students.
        Route::patch('students/{student}/active', [StudentController::class, 'toggleActive'])
            ->name('students.active');
        Route::apiResource('students', StudentController::class)
            ->only(['index', 'show']);

        // Select/autocomplete option lists — must stay above their apiResource.
        Route::get('activity-logs/filters', [ActivityLogController::class, 'filterOptions'])
            ->name('activity-logs.filters');

        Route::apiResource('activity-logs', ActivityLogController::class)
            ->parameters(['activity-logs' => 'activityLog'])
            ->only(['index', 'destroy']);
    });

// Shared endpoints — must stay below the staff group.
Route::middleware('auth:sanctum')->group(function () {
    Route::controller(ProfileController::class)
        ->prefix('profile')
        ->name('profile.')
        ->group(function () {
            Route::get('/', 'show')->name('show');
            Route::patch('/', 'update')->name('update');
        });

    // Notifications.
    Route::controller(NotificationController::class)
        ->prefix('notifications')
        ->name('notifications.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::patch('/read-all', 'markAllAsRead')->name('read-all');
            Route::patch('/{notification}/read', 'markAsRead')->name('read');
            Route::delete('/{notification}', 'destroy')->name('destroy');
        });
});

<?php

use App\Http\Controllers\Site\PublicPageController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\StudentPageController;
use App\Http\Controllers\Site\TeacherPageController;
use App\Http\Middleware\EnsureTeacherIsActive;
use App\Http\Middleware\EnsureVisitorId;
use App\Http\Middleware\SetPublicLocale;
use Illuminate\Support\Facades\Route;

// The student-facing site. Every page is locale-prefixed (/bn/…, /en/…) so a
// shared link always opens in the sender's language; bare / redirects to the
// visitor's remembered locale.

Route::get('/', function () {
    $locale = request()->cookie('locale');

    return redirect('/'.(in_array($locale, config('llb.locales'), true) ? $locale : 'bn'));
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/robots.txt', fn () => response(implode('
', [
    'User-agent: *',
    'Disallow: /admin',
    'Disallow: /v1/',
    '',
    'Sitemap: '.url('/sitemap.xml'),
]), 200, ['Content-Type' => 'text/plain']));

Route::middleware([SetPublicLocale::class, EnsureVisitorId::class])
    ->prefix('{locale}')
    ->where(['locale' => 'bn|en'])
    ->name('site.')
    ->group(function () {
        Route::controller(PublicPageController::class)->group(function () {
            Route::get('/', 'home')->name('home');
            Route::get('/programs/{program}', 'program')->name('programs.show');
            Route::get('/browse', 'browse')->name('browse');
            Route::get('/suggestions', 'browse')->defaults('type', 'suggestion')->name('suggestions');
            Route::get('/books', 'browse')->defaults('type', 'book')->name('books');
            Route::get('/notes', 'browse')->defaults('type', 'note')->name('notes');
            Route::get('/notices', 'notices')->name('notices.index');
            Route::get('/notices/{notice}', 'notice')->name('notices.show');
            Route::get('/subjects/{subject}', 'subject')->name('subjects.show');
            Route::get('/materials/{material}', 'material')->name('materials.show');
            Route::get('/exam-prep', 'examPrep')->name('exam-prep');
            Route::get('/questions', 'questions')->name('questions.index');
            Route::get('/model-tests', 'modelTests')->name('model-tests.index');
            Route::get('/model-tests/{modelTest}', 'modelTest')->name('model-tests.show');
        });

        Route::controller(StudentPageController::class)->group(function () {
            Route::middleware('guest:student')->group(function () {
                Route::get('/account/login', 'login')->name('account.login');
                Route::get('/account/register', 'register')->name('account.register');
                Route::get('/account/forgot-password', 'forgotPassword')->name('account.forgot-password');
                Route::get('/account/reset-password/{token}', 'resetPassword')->name('account.reset-password');
            });

            Route::middleware('auth:student')->group(function () {
                Route::get('/account/profile', 'profile')->name('account.profile');
                Route::get('/account/attempts', 'attempts')->name('account.attempts.index');
                Route::get('/account/attempts/{attempt}', 'attempt')->name('account.attempts.show');
                Route::get('/practice', 'practice')->name('practice');
                Route::get('/practice/run', 'practiceRun')->name('practice.run');
                Route::get('/model-tests/{modelTest}/attempts/{attempt}', 'attemptRunner')->name('model-tests.runner');
            });
        });

        Route::controller(TeacherPageController::class)->group(function () {
            Route::middleware('guest:teacher')->group(function () {
                Route::get('/teacher/login', 'login')->name('teacher.login');
                Route::get('/teacher/register', 'register')->name('teacher.register');
                Route::get('/teacher/registered', 'registered')->name('teacher.registered');
                Route::get('/teacher/forgot-password', 'forgotPassword')->name('teacher.forgot-password');
                Route::get('/teacher/reset-password/{token}', 'resetPassword')->name('teacher.reset-password');
            });

            Route::middleware(['auth:teacher', EnsureTeacherIsActive::class])->group(function () {
                Route::get('/teacher/profile', 'profile')->name('teacher.profile');

                Route::get('/teacher/routines', 'routines')->name('teacher.routines.index');
                Route::get('/teacher/routines/create', 'createRoutine')->name('teacher.routines.create');
                Route::get('/teacher/routines/{routine}/edit', 'editRoutine')->name('teacher.routines.edit');

                Route::get('/teacher/notices', 'notices')->name('teacher.notices.index');
                Route::get('/teacher/notices/create', 'createNotice')->name('teacher.notices.create');
                Route::get('/teacher/notices/{notice}/edit', 'editNotice')->name('teacher.notices.edit');

                Route::get('/teacher/notes', 'notes')->name('teacher.notes.index');
                Route::get('/teacher/notes/create', 'createNote')->name('teacher.notes.create');
                Route::get('/teacher/notes/{note}/edit', 'editNote')->name('teacher.notes.edit');
            });
        });

        Route::controller(StudentPageController::class)
            ->middleware('auth:student')
            ->group(function () {
                Route::get('/account/college/routine', 'collegeRoutine')->name('account.college.routine');
                Route::get('/account/college/notices', 'collegeNotices')->name('account.college.notices');
                Route::get('/account/college/notices/{notice}', 'collegeNotice')->name('account.college.notices.show');
                Route::get('/account/college/notes', 'collegeNotes')->name('account.college.notes');
            });
    });

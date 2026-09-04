<?php

use App\Http\Controllers\Site\PublicPageController;
use App\Http\Controllers\Site\SitemapController;
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
        });
    });

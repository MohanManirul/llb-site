<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Program;
use App\Models\StudyMaterial;
use App\Models\Subject;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [];

        foreach (config('llb.locales') as $locale) {
            $urls[] = ['loc' => url("/{$locale}"), 'priority' => '1.0'];
            $urls[] = ['loc' => url("/{$locale}/browse"), 'priority' => '0.9'];
            $urls[] = ['loc' => url("/{$locale}/notices"), 'priority' => '0.7'];

            foreach (Program::where('is_active', true)->pluck('slug') as $slug) {
                $urls[] = ['loc' => url("/{$locale}/programs/{$slug}"), 'priority' => '0.8'];
            }

            foreach (Subject::where('is_active', true)->pluck('slug') as $slug) {
                $urls[] = ['loc' => url("/{$locale}/subjects/{$slug}"), 'priority' => '0.7'];
            }

            $materials = StudyMaterial::query()
                ->publiclyVisible()
                ->orderByDesc('published_at')
                ->limit(5000)
                ->get(['slug', 'updated_at']);

            foreach ($materials as $material) {
                $urls[] = [
                    'loc' => url("/{$locale}/materials/{$material->slug}"),
                    'lastmod' => $material->updated_at?->toDateString(),
                    'priority' => '0.8',
                ];
            }

            $notices = Notice::query()
                ->publiclyVisible()
                ->unexpired()
                ->orderByDesc('published_at')
                ->limit(1000)
                ->get(['slug', 'updated_at']);

            foreach ($notices as $notice) {
                $urls[] = [
                    'loc' => url("/{$locale}/notices/{$notice->slug}"),
                    'lastmod' => $notice->updated_at?->toDateString(),
                    'priority' => '0.6',
                ];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $entry) {
            $xml .= '  <url>';
            $xml .= '<loc>'.e($entry['loc']).'</loc>';

            if (! empty($entry['lastmod'])) {
                $xml .= '<lastmod>'.e($entry['lastmod']).'</lastmod>';
            }

            $xml .= '<priority>'.$entry['priority'].'</priority>';
            $xml .= '</url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

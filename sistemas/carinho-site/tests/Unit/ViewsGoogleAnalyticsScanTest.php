<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ViewsGoogleAnalyticsScanTest extends TestCase
{
    public function test_layout_embeds_ga4_gtag_from_config(): void
    {
        $layout = dirname(__DIR__, 2).'/resources/views/layouts/app.blade.php';
        $content = (string) file_get_contents($layout);

        $this->assertStringContainsString(
            "https://www.googletagmanager.com/gtag/js?id={{ config('integrations.analytics.ga4_id') }}",
            $content
        );
        $this->assertStringContainsString("gtag('js', new Date());", $content);
        $this->assertStringContainsString(
            "gtag('config', '{{ config('integrations.analytics.ga4_id') }}');",
            $content
        );
        $this->assertStringContainsString(
            "config('integrations.analytics.enabled') && config('integrations.analytics.ga4_id')",
            $content
        );
    }

    public function test_page_and_legal_views_extend_app_layout(): void
    {
        $viewsPath = dirname(__DIR__, 2).'/resources/views';
        $missing = [];

        foreach (['pages', 'legal'] as $dir) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($viewsPath.'/'.$dir)
            );

            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                    continue;
                }

                $path = str_replace('\\', '/', $file->getPathname());
                $content = (string) file_get_contents($path);

                if (! str_contains($content, "@extends('layouts.app')")) {
                    $missing[] = $path;
                }
            }
        }

        $this->assertSame([], $missing, "Views sem layouts.app:\n".implode("\n", $missing));
    }
}

<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ViewsEmergencyTelScanTest extends TestCase
{
    public function test_emergency_numbers_in_views_use_tel_component_or_href(): void
    {
        $viewsPath = dirname(__DIR__, 2).'/resources/views';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
        $bare = [];

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            $content = (string) file_get_contents($path);

            if (str_ends_with($path, '/components/emergency-tel.blade.php')) {
                $this->assertStringContainsString('EmergencyTelLink::href', $content);
                continue;
            }

            if (!preg_match('/(?<!\d)(190|192|193|188)(?!\d)/', $content)) {
                continue;
            }

            $hasComponent = str_contains($content, '<x-emergency-tel');
            $hasTelHref = str_contains($content, 'tel:190')
                || str_contains($content, 'tel:192')
                || str_contains($content, 'tel:193')
                || str_contains($content, 'tel:188')
                || str_contains($content, 'EmergencyTelLink::linkify');

            if (!$hasComponent && !$hasTelHref) {
                $bare[] = $path;
            }
        }

        $this->assertSame([], $bare, "Número de emergência sem tel:\n".implode("\n", $bare));
    }
}

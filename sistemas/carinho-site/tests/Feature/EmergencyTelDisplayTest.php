<?php

namespace Tests\Feature;

use Tests\TestCase;

class EmergencyTelDisplayTest extends TestCase
{
    public function test_emergency_page_makes_public_numbers_callable(): void
    {
        $html = $this->visibleHtml(
            (string) $this->get('/legal/emergencias')->assertOk()->getContent()
        );

        foreach (['192', '193', '190', '188'] as $number) {
            $this->assertStringContainsString(
                'href="tel:'.$number.'"',
                $html,
                "O número {$number} em /legal/emergencias precisa de um link tel:."
            );
        }

        $this->assertGreaterThanOrEqual(2, substr_count($html, 'href="tel:192"'));
    }

    private function visibleHtml(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;

        return $html;
    }
}

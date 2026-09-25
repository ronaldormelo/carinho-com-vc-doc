<?php

namespace Tests\Feature;

use Tests\TestCase;

class CnpjDisplayTest extends TestCase
{
    public function test_public_pages_identify_the_company_cnpj(): void
    {
        $cnpj = (string) config('branding.contact.cnpj');

        $this->assertSame('69.279.245/0001-04', $cnpj);

        foreach (['/', '/quem-somos', '/contato', '/legal/termos', '/legal/privacidade'] as $path) {
            $this->get($path)->assertOk()->assertSee($cnpj, false);
        }
    }
}

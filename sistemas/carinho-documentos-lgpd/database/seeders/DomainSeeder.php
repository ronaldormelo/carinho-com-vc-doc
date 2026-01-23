<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DomainSeeder extends Seeder
{
    /**
     * Seed the domain tables.
     */
    public function run(): void
    {
        // Tipos de documento
        DB::table('domain_doc_type')->insert([
            ['id' => 1, 'code' => 'contrato_cliente', 'label' => 'Contrato cliente'],
            ['id' => 2, 'code' => 'contrato_cuidador', 'label' => 'Contrato cuidador'],
            ['id' => 3, 'code' => 'termos', 'label' => 'Termos de uso'],
            ['id' => 4, 'code' => 'privacidade', 'label' => 'Politica de privacidade'],
        ]);

        // Tipos de proprietario
        DB::table('domain_owner_type')->insert([
            ['id' => 1, 'code' => 'client', 'label' => 'Cliente'],
            ['id' => 2, 'code' => 'caregiver', 'label' => 'Cuidador'],
            ['id' => 3, 'code' => 'company', 'label' => 'Empresa'],
        ]);

        // Status de documento
        DB::table('domain_document_status')->insert([
            ['id' => 1, 'code' => 'draft', 'label' => 'Rascunho'],
            ['id' => 2, 'code' => 'signed', 'label' => 'Assinado'],
            ['id' => 3, 'code' => 'archived', 'label' => 'Arquivado'],
        ]);

        // Tipos de assinante
        DB::table('domain_signer_type')->insert([
            ['id' => 1, 'code' => 'client', 'label' => 'Cliente'],
            ['id' => 2, 'code' => 'caregiver', 'label' => 'Cuidador'],
            ['id' => 3, 'code' => 'company', 'label' => 'Empresa'],
        ]);

        // Metodos de assinatura
        DB::table('domain_signature_method')->insert([
            ['id' => 1, 'code' => 'otp', 'label' => 'Codigo OTP'],
            ['id' => 2, 'code' => 'click', 'label' => 'Clique para aceitar'],
            ['id' => 3, 'code' => 'certificate', 'label' => 'Certificado digital'],
        ]);

        // Acoes de acesso
        DB::table('domain_access_action')->insert([
            ['id' => 1, 'code' => 'view', 'label' => 'Visualizacao'],
            ['id' => 2, 'code' => 'download', 'label' => 'Download'],
            ['id' => 3, 'code' => 'sign', 'label' => 'Assinatura'],
            ['id' => 4, 'code' => 'delete', 'label' => 'Exclusao'],
        ]);

        // Tipos de solicitacao LGPD
        DB::table('domain_request_type')->insert([
            ['id' => 1, 'code' => 'export', 'label' => 'Exportacao de dados'],
            ['id' => 2, 'code' => 'delete', 'label' => 'Exclusao de dados'],
            ['id' => 3, 'code' => 'update', 'label' => 'Atualizacao de dados'],
        ]);

        // Status de solicitacao LGPD
        DB::table('domain_request_status')->insert([
            ['id' => 1, 'code' => 'open', 'label' => 'Aberta'],
            ['id' => 2, 'code' => 'in_progress', 'label' => 'Em andamento'],
            ['id' => 3, 'code' => 'done', 'label' => 'Concluida'],
            ['id' => 4, 'code' => 'rejected', 'label' => 'Rejeitada'],
        ]);

        // Tipos de titular de consentimento
        DB::table('domain_consent_subject_type')->insert([
            ['id' => 1, 'code' => 'client', 'label' => 'Cliente'],
            ['id' => 2, 'code' => 'caregiver', 'label' => 'Cuidador'],
        ]);
    }
}

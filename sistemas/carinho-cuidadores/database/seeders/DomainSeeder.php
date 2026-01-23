<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        // Status do cuidador
        DB::table('domain_caregiver_status')->insert([
            ['id' => 1, 'code' => 'pending', 'label' => 'Pendente'],
            ['id' => 2, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 3, 'code' => 'inactive', 'label' => 'Inativo'],
            ['id' => 4, 'code' => 'blocked', 'label' => 'Bloqueado'],
        ]);

        // Tipos de documento
        DB::table('domain_document_type')->insert([
            ['id' => 1, 'code' => 'id', 'label' => 'Documento de Identidade'],
            ['id' => 2, 'code' => 'cpf', 'label' => 'CPF'],
            ['id' => 3, 'code' => 'address', 'label' => 'Comprovante de Endereco'],
            ['id' => 4, 'code' => 'certificate', 'label' => 'Certificado de Curso'],
            ['id' => 5, 'code' => 'other', 'label' => 'Outro Documento'],
        ]);

        // Status do documento
        DB::table('domain_document_status')->insert([
            ['id' => 1, 'code' => 'pending', 'label' => 'Pendente'],
            ['id' => 2, 'code' => 'verified', 'label' => 'Verificado'],
            ['id' => 3, 'code' => 'rejected', 'label' => 'Rejeitado'],
        ]);

        // Tipos de cuidado
        DB::table('domain_care_type')->insert([
            ['id' => 1, 'code' => 'idoso', 'label' => 'Cuidado de Idosos'],
            ['id' => 2, 'code' => 'pcd', 'label' => 'Pessoa com Deficiencia'],
            ['id' => 3, 'code' => 'tea', 'label' => 'Transtorno do Espectro Autista'],
            ['id' => 4, 'code' => 'pos_operatorio', 'label' => 'Pos-Operatorio'],
        ]);

        // Niveis de habilidade
        DB::table('domain_skill_level')->insert([
            ['id' => 1, 'code' => 'basico', 'label' => 'Basico'],
            ['id' => 2, 'code' => 'intermediario', 'label' => 'Intermediario'],
            ['id' => 3, 'code' => 'avancado', 'label' => 'Avancado'],
        ]);

        // Status do contrato
        DB::table('domain_contract_status')->insert([
            ['id' => 1, 'code' => 'draft', 'label' => 'Rascunho'],
            ['id' => 2, 'code' => 'signed', 'label' => 'Assinado'],
            ['id' => 3, 'code' => 'active', 'label' => 'Ativo'],
            ['id' => 4, 'code' => 'closed', 'label' => 'Encerrado'],
        ]);
    }
}

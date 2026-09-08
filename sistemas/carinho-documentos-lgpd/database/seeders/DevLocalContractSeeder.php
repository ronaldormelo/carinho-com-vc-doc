<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DomainDocumentStatus;
use App\Models\DomainOwnerType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Contrato HTML local para impressão. Não usar em produção.
 */
class DevLocalContractSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('domain_doc_type')->updateOrInsert(
            ['id' => 1],
            ['code' => 'contrato_cliente', 'label' => 'Contrato cliente']
        );
        DB::table('domain_owner_type')->updateOrInsert(
            ['id' => 1],
            ['code' => 'client', 'label' => 'Cliente']
        );
        DB::table('domain_document_status')->updateOrInsert(
            ['id' => 1],
            ['code' => 'draft', 'label' => 'Rascunho']
        );

        $template = DocumentTemplate::query()
            ->where('doc_type_id', 1)
            ->where('active', true)
            ->orderBy('id')
            ->first();

        if (!$template) {
            $template = DocumentTemplate::query()->create([
                'doc_type_id' => 1,
                'version' => 'dev-local',
                'content' => '<div class="contract"><h1>Contrato de prestação de serviços</h1><p>Carinho com Você — documento local para impressão.</p><p>Atualizado em {{{data_atualizacao}}}</p></div>',
                'active' => true,
            ]);
        }

        $document = Document::query()->firstOrCreate(
            [
                'owner_type_id' => DomainOwnerType::CLIENT,
                'owner_id' => 1,
                'template_id' => $template->id,
            ],
            [
                'status_id' => DomainDocumentStatus::DRAFT,
            ]
        );

        $this->command?->info('Contrato local imprimível: GET /contratos/' . $document->id . '/imprimir');
    }
}

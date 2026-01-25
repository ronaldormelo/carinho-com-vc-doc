<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTemplateSeeder extends Seeder
{
    /**
     * Seed the document templates.
     */
    public function run(): void
    {
        $replyTo = config('branding.email.reply_to', 'contato@carinho.com.vc');
        $privacyEmail = config('branding.email.privacy', 'privacidade@carinho.com.vc');
        
        DB::table('document_templates')->insert([
            [
                'doc_type_id' => 1, // contrato_cliente
                'version' => '1.0',
                'content' => $this->getContratoClienteContent(),
                'active' => true,
            ],
            [
                'doc_type_id' => 2, // contrato_cuidador
                'version' => '1.0',
                'content' => $this->getContratoCuidadorContent(),
                'active' => true,
            ],
            [
                'doc_type_id' => 3, // termos
                'version' => '1.0',
                'content' => str_replace('contato@carinho.com.vc', $replyTo, $this->getTermosContent()),
                'active' => true,
            ],
            [
                'doc_type_id' => 4, // privacidade
                'version' => '1.0',
                'content' => str_replace('privacidade@carinho.com.vc', $privacyEmail, $this->getPrivacidadeContent()),
                'active' => true,
            ],
        ]);
    }

    private function getContratoClienteContent(): string
    {
        return <<<'HTML'
<div class="contract">
    <h1>CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE CUIDADO DOMICILIAR</h1>
    
    <p><strong>CONTRATANTE:</strong> {{cliente_nome}}, inscrito no CPF sob o nº {{cliente_cpf}}, residente em {{cliente_endereco}}.</p>
    
    <p><strong>CONTRATADA:</strong> CARINHO COM VOCÊ SERVIÇOS DE CUIDADO DOMICILIAR LTDA, inscrita no CNPJ sob o nº XX.XXX.XXX/0001-XX, com sede em São Paulo/SP.</p>
    
    <h2>CLÁUSULA PRIMEIRA - DO OBJETO</h2>
    <p>O presente contrato tem por objeto a prestação de serviços de intermediação de cuidadores domiciliares pela CONTRATADA ao CONTRATANTE, conforme condições estabelecidas neste instrumento.</p>
    
    <h2>CLÁUSULA SEGUNDA - DOS SERVIÇOS</h2>
    <p>A CONTRATADA compromete-se a:</p>
    <ul>
        <li>Selecionar cuidadores qualificados;</li>
        <li>Verificar antecedentes e referencias;</li>
        <li>Intermediar a contratacao do cuidador;</li>
        <li>Fornecer suporte durante a prestacao do servico;</li>
        <li>Realizar substituicao quando necessario.</li>
    </ul>
    
    <h2>CLÁUSULA TERCEIRA - DO PAGAMENTO</h2>
    <p>O CONTRATANTE pagará à CONTRATADA o valor de R$ {{valor_servico}} ({{valor_extenso}}) {{periodicidade}}, conforme plano contratado.</p>
    
    <h2>CLÁUSULA QUARTA - DA VIGÊNCIA</h2>
    <p>O presente contrato terá vigência de {{vigencia_meses}} meses, com início em {{data_inicio}}, podendo ser renovado automaticamente por períodos iguais e sucessivos.</p>
    
    <h2>CLÁUSULA QUINTA - DA RESCISÃO</h2>
    <p>O presente contrato poderá ser rescindido por qualquer das partes, mediante aviso prévio de 30 (trinta) dias.</p>
    
    <h2>CLÁUSULA SEXTA - DA PROTEÇÃO DE DADOS</h2>
    <p>As partes comprometem-se a tratar os dados pessoais em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).</p>
    
    <h2>CLÁUSULA SÉTIMA - DO FORO</h2>
    <p>Fica eleito o foro da comarca de São Paulo/SP para dirimir quaisquer dúvidas oriundas do presente contrato.</p>
    
    <p class="signature-date">São Paulo, {{data_assinatura}}</p>
    
    <div class="signatures">
        <div class="signature-block">
            <p>_________________________________</p>
            <p><strong>CONTRATANTE</strong></p>
            <p>{{cliente_nome}}</p>
        </div>
        <div class="signature-block">
            <p>_________________________________</p>
            <p><strong>CONTRATADA</strong></p>
            <p>Carinho com Você</p>
        </div>
    </div>
</div>
HTML;
    }

    private function getContratoCuidadorContent(): string
    {
        return <<<'HTML'
<div class="contract">
    <h1>CONTRATO DE PRESTAÇÃO DE SERVIÇOS - CUIDADOR</h1>
    
    <p><strong>CONTRATANTE:</strong> CARINHO COM VOCÊ SERVIÇOS DE CUIDADO DOMICILIAR LTDA, inscrita no CNPJ sob o nº XX.XXX.XXX/0001-XX.</p>
    
    <p><strong>PRESTADOR:</strong> {{cuidador_nome}}, inscrito no CPF sob o nº {{cuidador_cpf}}, residente em {{cuidador_endereco}}.</p>
    
    <h2>CLÁUSULA PRIMEIRA - DO OBJETO</h2>
    <p>O presente contrato tem por objeto a prestação de serviços de cuidado domiciliar pelo PRESTADOR, por intermédio da CONTRATANTE.</p>
    
    <h2>CLÁUSULA SEGUNDA - DAS OBRIGAÇÕES DO PRESTADOR</h2>
    <p>O PRESTADOR compromete-se a:</p>
    <ul>
        <li>Prestar serviços com zelo, dedicação e profissionalismo;</li>
        <li>Manter sigilo sobre informações dos pacientes;</li>
        <li>Cumprir horários e compromissos agendados;</li>
        <li>Comunicar imprevistos com antecedência;</li>
        <li>Manter documentação atualizada.</li>
    </ul>
    
    <h2>CLÁUSULA TERCEIRA - DA REMUNERAÇÃO</h2>
    <p>O PRESTADOR receberá por hora trabalhada, conforme tabela vigente e serviços prestados, sendo o pagamento realizado {{periodicidade_pagamento}}.</p>
    
    <h2>CLÁUSULA QUARTA - DA VIGÊNCIA</h2>
    <p>O presente contrato terá vigência indeterminada, podendo ser rescindido por qualquer das partes mediante aviso prévio de 15 (quinze) dias.</p>
    
    <h2>CLÁUSULA QUINTA - DA PROTEÇÃO DE DADOS</h2>
    <p>O PRESTADOR compromete-se a tratar os dados pessoais dos pacientes em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018), mantendo sigilo absoluto.</p>
    
    <h2>CLÁUSULA SEXTA - DO FORO</h2>
    <p>Fica eleito o foro da comarca de São Paulo/SP para dirimir quaisquer dúvidas oriundas do presente contrato.</p>
    
    <p class="signature-date">São Paulo, {{data_assinatura}}</p>
    
    <div class="signatures">
        <div class="signature-block">
            <p>_________________________________</p>
            <p><strong>CONTRATANTE</strong></p>
            <p>Carinho com Você</p>
        </div>
        <div class="signature-block">
            <p>_________________________________</p>
            <p><strong>PRESTADOR</strong></p>
            <p>{{cuidador_nome}}</p>
        </div>
    </div>
</div>
HTML;
    }

    private function getTermosContent(): string
    {
        return <<<'HTML'
<div class="terms">
    <h1>TERMOS DE USO</h1>
    <p><strong>Última atualização:</strong> {{data_atualizacao}}</p>
    
    <h2>1. ACEITAÇÃO DOS TERMOS</h2>
    <p>Ao utilizar os serviços da Carinho com Você, você concorda com estes Termos de Uso. Se você não concordar, não utilize nossos serviços.</p>
    
    <h2>2. DESCRIÇÃO DOS SERVIÇOS</h2>
    <p>A Carinho com Você oferece serviços de intermediação entre clientes que necessitam de cuidadores domiciliares e profissionais qualificados para prestação desses serviços.</p>
    
    <h2>3. CADASTRO E CONTA</h2>
    <p>Para utilizar nossos serviços, você deve fornecer informações verdadeiras e completas. Você é responsável por manter a confidencialidade de sua conta.</p>
    
    <h2>4. USO ACEITÁVEL</h2>
    <p>Você concorda em utilizar nossos serviços apenas para fins legítimos e de acordo com estes Termos e a legislação aplicável.</p>
    
    <h2>5. PROPRIEDADE INTELECTUAL</h2>
    <p>Todo o conteúdo disponibilizado é de propriedade da Carinho com Você ou de seus licenciadores.</p>
    
    <h2>6. LIMITAÇÃO DE RESPONSABILIDADE</h2>
    <p>A Carinho com Você não se responsabiliza por danos indiretos, incidentais ou consequentes decorrentes do uso de nossos serviços.</p>
    
    <h2>7. ALTERAÇÕES NOS TERMOS</h2>
    <p>Reservamo-nos o direito de modificar estes Termos a qualquer momento. As alterações entrarão em vigor após publicação.</p>
    
    <h2>8. CONTATO</h2>
    <p>Em caso de dúvidas, entre em contato: contato@carinho.com.vc</p>
    
    <p class="footer">© Carinho com Você - Todos os direitos reservados</p>
</div>
HTML;
    }

    private function getPrivacidadeContent(): string
    {
        return <<<'HTML'
<div class="privacy-policy">
    <h1>POLÍTICA DE PRIVACIDADE</h1>
    <p><strong>Última atualização:</strong> {{data_atualizacao}}</p>
    
    <h2>1. INTRODUÇÃO</h2>
    <p>A Carinho com Você está comprometida em proteger sua privacidade. Esta política descreve como coletamos, usamos e protegemos seus dados pessoais em conformidade com a Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018).</p>
    
    <h2>2. DADOS COLETADOS</h2>
    <p>Coletamos os seguintes dados pessoais:</p>
    <ul>
        <li><strong>Dados de identificação:</strong> nome, CPF, RG, data de nascimento;</li>
        <li><strong>Dados de contato:</strong> endereço, telefone, e-mail;</li>
        <li><strong>Dados de saúde:</strong> informações sobre condições de saúde do paciente (quando aplicável);</li>
        <li><strong>Dados profissionais:</strong> formação, experiência, certificações (para cuidadores).</li>
    </ul>
    
    <h2>3. FINALIDADE DO TRATAMENTO</h2>
    <p>Utilizamos seus dados para:</p>
    <ul>
        <li>Prestação dos serviços contratados;</li>
        <li>Comunicação sobre serviços e atualizações;</li>
        <li>Cumprimento de obrigações legais;</li>
        <li>Melhoria de nossos serviços.</li>
    </ul>
    
    <h2>4. BASE LEGAL</h2>
    <p>O tratamento de dados é realizado com base em:</p>
    <ul>
        <li>Consentimento do titular;</li>
        <li>Execução de contrato;</li>
        <li>Cumprimento de obrigação legal;</li>
        <li>Interesse legítimo da empresa.</li>
    </ul>
    
    <h2>5. COMPARTILHAMENTO DE DADOS</h2>
    <p>Seus dados podem ser compartilhados com:</p>
    <ul>
        <li>Cuidadores (para prestação do serviço);</li>
        <li>Prestadores de serviços de tecnologia;</li>
        <li>Autoridades, quando exigido por lei.</li>
    </ul>
    
    <h2>6. SEUS DIREITOS</h2>
    <p>Você tem direito a:</p>
    <ul>
        <li>Acessar seus dados pessoais;</li>
        <li>Corrigir dados incompletos ou desatualizados;</li>
        <li>Solicitar a exclusão de seus dados;</li>
        <li>Revogar consentimentos;</li>
        <li>Solicitar portabilidade dos dados.</li>
    </ul>
    
    <h2>7. SEGURANÇA</h2>
    <p>Implementamos medidas técnicas e organizacionais para proteger seus dados, incluindo criptografia, controle de acesso e monitoramento.</p>
    
    <h2>8. RETENÇÃO DE DADOS</h2>
    <p>Mantemos seus dados pelo período necessário para cumprir as finalidades descritas ou conforme exigido por lei.</p>
    
    <h2>9. CONTATO DO ENCARREGADO (DPO)</h2>
    <p>Para exercer seus direitos ou esclarecer dúvidas sobre privacidade:</p>
    <p>E-mail: privacidade@carinho.com.vc</p>
    
    <h2>10. ALTERAÇÕES</h2>
    <p>Esta política pode ser atualizada periodicamente. Recomendamos sua revisão regular.</p>
    
    <p class="footer">© Carinho com Você - Todos os direitos reservados</p>
</div>
HTML;
    }
}

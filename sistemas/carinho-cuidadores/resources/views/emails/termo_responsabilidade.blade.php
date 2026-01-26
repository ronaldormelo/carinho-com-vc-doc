<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termo de Responsabilidade - {{ $brandName }}</title>
    <style>
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            line-height: 1.8;
            color: #1F2933;
            background-color: #FFFFFF;
            margin: 0;
            padding: 40px;
        }
        .document {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #5BBFAD;
            padding-bottom: 24px;
            margin-bottom: 32px;
        }
        .logo {
            text-align: center;
        }
        .logo img {
            height: 50px;
            width: auto;
            margin-bottom: 8px;
        }
        h1 {
            color: #1F2933;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 16px 0 0;
        }
        h2 {
            color: #1F2933;
            font-size: 16px;
            margin: 24px 0 12px;
            border-bottom: 1px solid #E4E7EB;
            padding-bottom: 8px;
        }
        p {
            margin: 0 0 12px;
            text-align: justify;
        }
        .clause {
            margin-bottom: 24px;
        }
        .parties {
            background-color: #F4F7F9;
            padding: 16px;
            border-radius: 6px;
            margin: 24px 0;
        }
        .parties p {
            margin: 8px 0;
        }
        ul {
            margin: 12px 0;
            padding-left: 24px;
        }
        li {
            margin-bottom: 8px;
        }
        .signature-area {
            margin-top: 48px;
            border-top: 1px solid #E4E7EB;
            padding-top: 24px;
        }
        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            margin-top: 48px;
        }
        .signature-line {
            border-top: 1px solid #1F2933;
            padding-top: 8px;
            margin-top: 60px;
        }
        .date-location {
            text-align: right;
            margin-top: 32px;
            color: #616E7C;
        }
        .footer {
            margin-top: 48px;
            text-align: center;
            font-size: 12px;
            color: #9AA5B1;
        }
    </style>
</head>
<body>
    <div class="document">
        <div class="header">
            <div class="logo">
                <img src="{{ asset(config('branding.assets.logo.primary')) }}" alt="{{ $brandName }}" />
            </div>
            <h1>Termo de Responsabilidade do Cuidador</h1>
        </div>

        <div class="parties">
            <p><strong>CONTRATANTE:</strong> {{ $brandName }}, pessoa jurídica de direito privado, com sede em [endereço], inscrita no CNPJ sob o nº [CNPJ].</p>
            <p><strong>CUIDADOR(A):</strong> {{ $caregiver->name }}, portador(a) do CPF nº [CPF], residente em {{ $caregiver->city }}, telefone {{ $caregiver->phone }}.</p>
        </div>

        <div class="clause">
            <h2>CLÁUSULA 1ª - DO OBJETO</h2>
            <p>O presente termo tem por objeto estabelecer as condições e responsabilidades para a prestação de serviços de cuidador(a) domiciliar intermediados pela {{ $brandName }}.</p>
        </div>

        <div class="clause">
            <h2>CLÁUSULA 2ª - DAS OBRIGAÇÕES DO CUIDADOR</h2>
            <p>O(A) CUIDADOR(A) compromete-se a:</p>
            <ul>
                <li>Prestar os serviços com zelo, dedicação, pontualidade e responsabilidade;</li>
                <li>Manter sigilo sobre informações pessoais e familiares dos pacientes atendidos;</li>
                <li>Seguir as orientações médicas e familiares relacionadas ao cuidado;</li>
                <li>Comunicar imediatamente qualquer intercorrência ou alteração no estado de saúde do paciente;</li>
                <li>Realizar check-in e check-out conforme procedimentos estabelecidos;</li>
                <li>Manter seus dados cadastrais e disponibilidade sempre atualizados;</li>
                <li>Utilizar vestimenta adequada e apresentação pessoal condizente;</li>
                <li>Não utilizar pertences do paciente ou familiares sem autorização;</li>
                <li>Não divulgar fotos, vídeos ou informações dos pacientes atendidos.</li>
            </ul>
        </div>

        <div class="clause">
            <h2>CLÁUSULA 3ª - DA CONDUTA PROFISSIONAL</h2>
            <p>O(A) CUIDADOR(A) deve manter conduta ética e profissional, tratando pacientes e familiares com respeito, empatia e dignidade, evitando qualquer forma de discriminação, negligência ou maus-tratos.</p>
        </div>

        <div class="clause">
            <h2>CLÁUSULA 4ª - DA COMUNICAÇÃO</h2>
            <p>Toda comunicação oficial será realizada através dos canais disponibilizados pela {{ $brandName }}, incluindo WhatsApp e e-mail cadastrados. O(A) CUIDADOR(A) compromete-se a manter estes canais ativos e responder em tempo hábil.</p>
        </div>

        <div class="clause">
            <h2>CLÁUSULA 5ª - DAS SUBSTITUIÇÕES</h2>
            <p>Em caso de impossibilidade de comparecer a um serviço agendado, o(a) CUIDADOR(A) deve comunicar com antecedência mínima de 24 horas, salvo em casos de emergência comprovada, para que seja providenciada substituição adequada.</p>
        </div>

        <div class="clause">
            <h2>CLÁUSULA 6ª - DA EXCLUSÃO</h2>
            <p>O(A) CUIDADOR(A) poderá ser desligado(a) da plataforma em caso de:</p>
            <ul>
                <li>Faltas não justificadas ou atrasos recorrentes;</li>
                <li>Avaliações negativas reiteradas;</li>
                <li>Conduta inadequada ou antiética;</li>
                <li>Quebra de sigilo ou privacidade;</li>
                <li>Descumprimento das obrigações previstas neste termo.</li>
            </ul>
        </div>

        <div class="clause">
            <h2>CLÁUSULA 7ª - DA LGPD</h2>
            <p>O(A) CUIDADOR(A) autoriza o tratamento de seus dados pessoais pela {{ $brandName }} para fins de cadastro, comunicação e intermediação de serviços, conforme a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).</p>
        </div>

        <div class="clause">
            <h2>CLÁUSULA 8ª - DAS DISPOSIÇÕES GERAIS</h2>
            <p>Este termo entra em vigor na data de sua assinatura e permanece válido enquanto durar a relação de parceria entre as partes. Eventuais modificações serão comunicadas com antecedência e devidamente formalizadas.</p>
        </div>

        <div class="date-location">
            <p>{{ $caregiver->city }}, {{ now()->format('d/m/Y') }}</p>
        </div>

        <div class="signature-area">
            <div class="signature-box">
                <div class="signature-line">
                    <strong>{{ $brandName }}</strong><br>
                    <span style="font-size: 12px;">Contratante</span>
                </div>
            </div>
            <div class="signature-box" style="float: right;">
                <div class="signature-line">
                    <strong>{{ $caregiver->name }}</strong><br>
                    <span style="font-size: 12px;">Cuidador(a)</span>
                </div>
            </div>
        </div>

        <div style="clear: both;"></div>

        <div class="footer">
            <p>{{ $brandName }} - carinho.com.vc</p>
            <p>Documento gerado eletronicamente em {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

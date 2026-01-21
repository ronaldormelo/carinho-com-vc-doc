<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Aceite Digital - Carinho com Você</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --color-primary-500: #0ea5e9;
            --color-primary-600: #0284c7;
            --color-primary-700: #0369a1;
            --color-secondary-500: #22c55e;
            --color-gray-50: #f9fafb;
            --color-gray-100: #f3f4f6;
            --color-gray-200: #e5e7eb;
            --color-gray-600: #4b5563;
            --color-gray-800: #1f2937;
            --color-gray-900: #111827;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--color-gray-50);
            color: var(--color-gray-800);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
        }
        
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            padding: 2rem;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-primary-600);
        }
        
        .logo-text span {
            color: #f43f5e;
        }
        
        h1 {
            font-size: 1.5rem;
            color: var(--color-gray-900);
            margin: 0 0 0.5rem;
            text-align: center;
        }
        
        .subtitle {
            color: var(--color-gray-600);
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .contract-info {
            background: var(--color-gray-50);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .contract-info h3 {
            margin: 0 0 1rem;
            font-size: 1rem;
            color: var(--color-gray-800);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--color-gray-200);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: var(--color-gray-600);
        }
        
        .info-value {
            font-weight: 500;
        }
        
        .terms-box {
            border: 1px solid var(--color-gray-200);
            border-radius: 0.75rem;
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: var(--color-gray-600);
            line-height: 1.6;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-group input {
            width: 1.25rem;
            height: 1.25rem;
            margin-top: 0.125rem;
        }
        
        .checkbox-group label {
            font-size: 0.875rem;
            color: var(--color-gray-600);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 1rem;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: var(--color-primary-600);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--color-primary-700);
        }
        
        .btn-primary:disabled {
            background: var(--color-gray-200);
            cursor: not-allowed;
        }
        
        .success-message {
            text-align: center;
            padding: 2rem;
        }
        
        .success-icon {
            width: 64px;
            height: 64px;
            background: var(--color-secondary-500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .success-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.75rem;
            color: var(--color-gray-600);
        }
        
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <span class="logo-text">Carinho<span>CRM</span></span>
            </div>
            
            <!-- Form View -->
            <div id="form-view">
                <h1>Aceite Digital de Contrato</h1>
                <p class="subtitle">Revise os termos e confirme sua assinatura digital</p>
                
                <div class="contract-info">
                    <h3>Informações do Contrato</h3>
                    <div class="info-row">
                        <span class="info-label">Cliente</span>
                        <span class="info-value" id="client-name">Carregando...</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tipo de Serviço</span>
                        <span class="info-value" id="service-type">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Valor Mensal</span>
                        <span class="info-value" id="monthly-value">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Início</span>
                        <span class="info-value" id="start-date">-</span>
                    </div>
                </div>
                
                <div class="terms-box">
                    <strong>TERMO DE PRESTAÇÃO DE SERVIÇOS DE CUIDADO DOMICILIAR</strong>
                    <br><br>
                    Pelo presente instrumento, as partes acordam os seguintes termos:
                    <br><br>
                    <strong>1. DO OBJETO</strong><br>
                    O presente contrato tem por objeto a prestação de serviços de cuidado domiciliar, conforme especificações acordadas entre as partes.
                    <br><br>
                    <strong>2. DAS OBRIGAÇÕES</strong><br>
                    A CONTRATADA se compromete a disponibilizar profissionais qualificados e avaliados para a prestação dos serviços contratados.
                    <br><br>
                    <strong>3. DO PAGAMENTO</strong><br>
                    O pagamento deverá ser realizado conforme condições acordadas, até o vencimento estabelecido.
                    <br><br>
                    <strong>4. DA VIGÊNCIA</strong><br>
                    O presente contrato terá vigência conforme período especificado acima.
                    <br><br>
                    <strong>5. DO CANCELAMENTO</strong><br>
                    O cancelamento poderá ser solicitado com antecedência mínima de 48 horas.
                    <br><br>
                    <strong>6. DA PROTEÇÃO DE DADOS (LGPD)</strong><br>
                    As partes se comprometem a tratar os dados pessoais de acordo com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="accept-terms">
                    <label for="accept-terms">
                        Li e aceito os termos do contrato de prestação de serviços e autorizo o tratamento dos meus dados pessoais conforme a Política de Privacidade.
                    </label>
                </div>
                
                <button type="button" class="btn btn-primary" id="sign-btn" disabled onclick="signContract()">
                    Assinar Digitalmente
                </button>
            </div>
            
            <!-- Success View -->
            <div id="success-view" class="hidden">
                <div class="success-message">
                    <div class="success-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1>Contrato Assinado!</h1>
                    <p class="subtitle">Seu contrato foi assinado digitalmente com sucesso. Você receberá uma cópia por e-mail.</p>
                    <p style="font-size: 0.875rem; color: var(--color-gray-600);">
                        Obrigado por confiar na <strong>Carinho com Você</strong>.
                    </p>
                </div>
            </div>
            
            <p class="footer-text">
                Carinho com Você - Cuidado domiciliar com confiança<br>
                carinho.com.vc
            </p>
        </div>
    </div>
    
    <script>
        const token = '{{ $token }}';
        
        document.getElementById('accept-terms').addEventListener('change', function() {
            document.getElementById('sign-btn').disabled = !this.checked;
        });
        
        async function signContract() {
            const btn = document.getElementById('sign-btn');
            btn.disabled = true;
            btn.textContent = 'Processando...';
            
            try {
                const response = await fetch(`/contract/${token}/accept`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                
                if (response.ok) {
                    document.getElementById('form-view').classList.add('hidden');
                    document.getElementById('success-view').classList.remove('hidden');
                } else {
                    alert('Erro ao processar assinatura. Tente novamente.');
                    btn.disabled = false;
                    btn.textContent = 'Assinar Digitalmente';
                }
            } catch (error) {
                console.error(error);
                alert('Erro de conexão. Tente novamente.');
                btn.disabled = false;
                btn.textContent = 'Assinar Digitalmente';
            }
        }
    </script>
</body>
</html>

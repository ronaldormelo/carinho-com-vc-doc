<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura de Contrato - Carinho com Voce</title>
    <link rel="icon" href="{{ asset(config('branding.assets.logo.favicon', '/images/favicon.ico')) }}" type="image/x-icon">
    <link rel="stylesheet" href="/css/brand.css">
    <style>
        .page-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: var(--spacing-4) 0;
        }
        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
        }
        .logo img {
            height: 40px;
            width: auto;
        }
        .main-content {
            flex: 1;
            padding: var(--spacing-8) 0;
        }
        .contract-viewer {
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-6);
            margin-bottom: var(--spacing-6);
            max-height: 500px;
            overflow-y: auto;
        }
        .signature-section {
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-6);
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: var(--spacing-6);
        }
        .step {
            display: flex;
            align-items: center;
            color: var(--color-text-muted);
        }
        .step.active {
            color: var(--color-primary);
        }
        .step.completed {
            color: var(--color-success);
        }
        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: var(--spacing-2);
        }
        .step.active .step-number {
            background-color: var(--color-primary);
            color: white;
        }
        .step.completed .step-number {
            background-color: var(--color-success);
            color: white;
        }
        .step-divider {
            width: 60px;
            height: 2px;
            background-color: var(--border-color);
            margin: 0 var(--spacing-4);
        }
        .otp-input {
            display: flex;
            gap: var(--spacing-2);
            justify-content: center;
            margin: var(--spacing-6) 0;
        }
        .otp-input input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: var(--font-size-2xl);
            font-weight: 600;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
        }
        .otp-input input:focus {
            outline: none;
            border-color: var(--color-primary);
        }
        .phone-input-group {
            display: flex;
            gap: var(--spacing-2);
            margin-bottom: var(--spacing-4);
        }
        .phone-input-group input {
            flex: 1;
        }
        .success-message {
            text-align: center;
            padding: var(--spacing-8);
        }
        .success-icon {
            font-size: 64px;
            color: var(--color-success);
            margin-bottom: var(--spacing-4);
        }
        footer {
            background-color: var(--bg-primary);
            border-top: 1px solid var(--border-color);
            padding: var(--spacing-4) 0;
            text-align: center;
            font-size: var(--font-size-sm);
            color: var(--color-text-muted);
        }
    </style>
</head>
<body>
    <div class="page-container">
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <img src="{{ asset(config('branding.assets.logo.primary')) }}" alt="{{ config('branding.name') }}" />
                </div>
                <span class="text-muted">Assinatura Digital</span>
            </div>
        </nav>

        <main class="main-content">
            <div class="container">
                <div class="step-indicator" id="stepIndicator">
                    <div class="step active" data-step="1">
                        <span class="step-number">1</span>
                        <span>Revisar</span>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step" data-step="2">
                        <span class="step-number">2</span>
                        <span>Verificar</span>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step" data-step="3">
                        <span class="step-number">3</span>
                        <span>Concluir</span>
                    </div>
                </div>

                <!-- Step 1: Review Contract -->
                <div id="step1" class="step-content">
                    <div class="contract-viewer" id="contractContent">
                        <p class="text-muted">Carregando contrato...</p>
                    </div>

                    <div class="consent-checkbox">
                        <input type="checkbox" id="acceptTerms">
                        <label class="consent-text" for="acceptTerms">
                            Li e concordo com os termos do contrato acima e com a
                            <a href="/privacidade" target="_blank">Politica de Privacidade</a>.
                        </label>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary" id="btnProceed" disabled>
                            Prosseguir para Assinatura
                        </button>
                    </div>
                </div>

                <!-- Step 2: OTP Verification -->
                <div id="step2" class="step-content" style="display: none;">
                    <div class="signature-section">
                        <h3>Verificacao de Identidade</h3>
                        <p class="text-muted">
                            Para garantir a seguranca da assinatura, enviaremos um codigo
                            de verificacao para seu WhatsApp.
                        </p>

                        <div class="phone-input-group">
                            <input type="tel" id="phoneInput" class="form-control"
                                   placeholder="(11) 99999-9999" maxlength="15">
                            <button class="btn btn-primary" id="btnSendOtp">
                                Enviar Codigo
                            </button>
                        </div>

                        <div id="otpSection" style="display: none;">
                            <p class="text-muted mt-4">
                                Digite o codigo de 6 digitos enviado:
                            </p>
                            <div class="otp-input">
                                <input type="text" maxlength="1" class="otp-digit">
                                <input type="text" maxlength="1" class="otp-digit">
                                <input type="text" maxlength="1" class="otp-digit">
                                <input type="text" maxlength="1" class="otp-digit">
                                <input type="text" maxlength="1" class="otp-digit">
                                <input type="text" maxlength="1" class="otp-digit">
                            </div>
                            <button class="btn btn-primary" id="btnVerifyOtp">
                                Verificar e Assinar
                            </button>
                            <p class="text-muted mt-4" style="font-size: 13px;">
                                Nao recebeu? <a href="#" id="btnResendOtp">Reenviar codigo</a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Success -->
                <div id="step3" class="step-content" style="display: none;">
                    <div class="signature-section">
                        <div class="success-message">
                            <div class="success-icon">✓</div>
                            <h2>Contrato Assinado com Sucesso!</h2>
                            <p class="text-muted">
                                Uma copia do contrato assinado foi enviada para seu email.
                            </p>
                            <a href="#" id="downloadLink" class="btn btn-primary mt-4">
                                Baixar Contrato
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <div class="container">
                <p>&copy; 2026 Carinho com Voce. Todos os direitos reservados.</p>
            </div>
        </footer>
    </div>

    <script>
        const token = '{{ $token }}';

        // Phone mask
        document.getElementById('phoneInput').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            if (value.length > 6) {
                value = `(${value.slice(0, 2)}) ${value.slice(2, 7)}-${value.slice(7)}`;
            } else if (value.length > 2) {
                value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
            } else if (value.length > 0) {
                value = `(${value}`;
            }
            e.target.value = value;
        });

        // Enable proceed button when checkbox is checked
        document.getElementById('acceptTerms').addEventListener('change', function(e) {
            document.getElementById('btnProceed').disabled = !e.target.checked;
        });

        // Step navigation
        document.getElementById('btnProceed').addEventListener('click', function() {
            showStep(2);
        });

        // OTP input navigation
        document.querySelectorAll('.otp-digit').forEach((input, index, inputs) => {
            input.addEventListener('input', function() {
                if (this.value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });

        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.style.display = 'none');
            document.getElementById(`step${step}`).style.display = 'block';

            document.querySelectorAll('.step').forEach((el, i) => {
                el.classList.remove('active', 'completed');
                if (i + 1 < step) el.classList.add('completed');
                if (i + 1 === step) el.classList.add('active');
            });
        }

        // Load contract content
        fetch(`/api/public/contract/${token}`)
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    document.getElementById('contractContent').innerHTML = '<p>Contrato carregado.</p>';
                } else {
                    document.getElementById('contractContent').innerHTML =
                        '<p class="text-danger">Erro ao carregar contrato. Token invalido ou expirado.</p>';
                }
            })
            .catch(err => {
                document.getElementById('contractContent').innerHTML =
                    '<p class="text-danger">Erro ao carregar contrato.</p>';
            });
    </script>
</body>
</html>

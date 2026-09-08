# Glossário

Termos usados na documentação e nos sistemas. Preferir estas palavras em novos textos.

| Termo | Significado |
|-------|-------------|
| **Família / cliente** | Contratante do cuidado. No CRM vira `Client` após conversão do lead. |
| **Paciente** | Pessoa que recebe o cuidado (pode não ser o responsável financeiro). |
| **Cuidador** | Profissional alocado. Módulo: `carinho-cuidadores`. |
| **Lead** | Contato ainda não cliente. Origem com UTM no Site/Marketing/CRM. |
| **ICP** | Perfil de cliente ideal (renda, urgência, recorrência) — doc 01. |
| **Horista / diário / mensal** | Tipos de serviço cobrados. |
| **Inbox** | Lista de conversas no Atendimento (WhatsApp, e-mail, telefone). |
| **Triagem** | Checklist obrigatório antes da proposta. |
| **Funil** | Status da conversa: novo → triagem → proposta → aguardando → ativo / perdido. |
| **SLA** | Tempo máximo de primeira resposta ou resolução, por prioridade. |
| **N1 / N2 / N3** | Níveis de suporte (atendente, supervisão, gestão). |
| **Match** | Pontuação cliente × cuidador na Operação (skills, agenda, região, nota). |
| **Check-in / check-out** | Confirmação de chegada e encerramento do plantão. |
| **Substituição** | Troca de cuidador; busca limitada no tempo. |
| **Hub** | `carinho-integracoes`: eventos, mapeamentos, retry, DLQ. |
| **DLQ** | Dead letter queue: eventos que falharam após as tentativas. |
| **Token interno** | Segredo compartilhado entre dois sistemas para API. |
| **Z-API** | Provedor do WhatsApp Business usado no projeto. |
| **UTM** | Parâmetros de campanha (`utm_source`, etc.) gravados no lead. |
| **Titular** | Pessoa cujos dados pessoais são tratados (LGPD). |
| **OTP** | Código de uso único para assinar contrato via WhatsApp. |
| **Repasse / payout** | Pagamento semanal ao cuidador (Financeiro / Stripe Connect). |
| **Pré-pago** | Cliente paga antes do serviço. |
| **Schema `carinho_*`** | Banco MariaDB de um módulo, isolado dos demais. |
| **P95** | Percentil 95 do tempo de resposta (meta de desenho, não medição). |
| **RTO / RPO** | Tempo máximo de recuperação / perda máxima de dados em desastre. |

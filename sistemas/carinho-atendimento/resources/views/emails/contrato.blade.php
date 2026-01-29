<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8">
    <title>Contrato</title>
  </head>
  <body style="margin:0;padding:0;background:#F4F7F9;font-family:Arial,Helvetica,sans-serif;color:#1a2b32;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F4F7F9;padding:24px 0;">
      <tr>
        <td align="center">
          <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
            <tr>
              <td style="background:#5BBFAD;color:#ffffff;padding:18px 24px;">
                <strong>{{ config('branding.name') }}</strong>
              </td>
            </tr>
            <tr>
              <td style="padding:24px;">
                <p>Ola {{ $name ?? 'Cliente' }},</p>
                <p>Segue o contrato para formalizacao do atendimento. Se tiver qualquer duvida, estamos a disposicao.</p>
                <p><strong>Dados do contrato:</strong></p>
                <ul style="padding-left:18px;">
                  <li>Servico: {{ $service ?? 'Cuidado domiciliar' }}</li>
                  <li>Periodo: {{ $period ?? 'A combinar' }}</li>
                  <li>Valor: {{ $price ?? 'A combinar' }}</li>
                </ul>
                <p>Assim que recebermos sua confirmacao, seguimos com o inicio do cuidado.</p>
                <p style="margin-top:24px;">Atenciosamente,<br>{{ config('branding.email.signature_name') }}</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>

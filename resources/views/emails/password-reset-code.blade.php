<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Código de recuperação de senha</title>
</head>
<body style="margin: 0; background: #f3f6fb; color: #172033; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f3f6fb; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 560px; overflow: hidden; border-radius: 18px; background: #ffffff; box-shadow: 0 18px 45px rgba(23, 32, 51, 0.12);">
                    <tr>
                        <td style="background: #101827; padding: 28px 32px; text-align: center;">
                            <img src="{{ $logoUrl }}" alt="Meu Controle" width="148" style="display: inline-block; max-width: 148px; height: auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 34px 34px 12px;">
                            <p style="margin: 0 0 10px; color: #0f766e; font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;">Recuperação de senha</p>
                            <h1 style="margin: 0; color: #172033; font-size: 26px; line-height: 1.25;">Use este código para redefinir sua senha</h1>
                            <p style="margin: 18px 0 0; color: #526071; font-size: 16px; line-height: 1.6;">
                                Recebemos uma solicitação para alterar a senha da sua conta no Meu Controle.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 18px 34px;">
                            <div style="border: 1px solid #c9f4ec; border-radius: 14px; background: #ecfdf7; padding: 22px; text-align: center;">
                                <p style="margin: 0 0 12px; color: #0f766e; font-size: 14px; font-weight: 700;">Código de verificação</p>
                                <p style="margin: 0; color: #0f172a; font-family: 'Courier New', Courier, monospace; font-size: 36px; font-weight: 700; letter-spacing: 0.22em;">{{ $code }}</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 34px 34px;">
                            <p style="margin: 0; color: #526071; font-size: 15px; line-height: 1.6;">
                                Informe esse código no aplicativo para criar uma nova senha. Por segurança, ele expira em {{ $expiresInMinutes }} minutos e só pode ser usado uma vez.
                            </p>
                            <div style="margin-top: 22px; border-left: 4px solid #2563eb; border-radius: 8px; background: #eff6ff; padding: 14px 16px;">
                                <p style="margin: 0; color: #314155; font-size: 14px; line-height: 1.55;">
                                    Se você não solicitou essa recuperação, ignore este e-mail. Sua senha atual continuará a mesma.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #e5eaf1; padding: 20px 34px 28px; text-align: center;">
                            <p style="margin: 0; color: #8a96a8; font-size: 12px; line-height: 1.5;">
                                Meu Controle<br>
                                Organização e acompanhamento da sua rotina de saúde.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

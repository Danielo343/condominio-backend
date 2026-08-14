<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperación de Contraseña</title>
</head>
<body style="background-color: #0f172a; color: #f8fafc; font-family: Arial, sans-serif; padding: 40px; text-align: center;">
    <div style="max-width: 500px; margin: 0 auto; background-color: #1e293b; padding: 30px; border-radius: 16px; border: 1px solid #334155;">
        <h2 style="color: #c084fc; margin-bottom: 5px;">CondoMaster Pro</h2>
        <p style="color: #94a3b8; font-size: 14px; margin-top: 0;">Gestión Integral de Condominios</p>
        <hr style="border: 0; border-top: 1px solid #334155; margin: 20px 0;">
        <h3 style="color: #ffffff;">Recuperación de Contraseña</h3>
        <p style="color: #cbd5e1; font-size: 14px; margin-bottom: 25px;">
            Has solicitado restablecer tu contraseña. Utiliza el siguiente código de 6 dígitos para continuar:
        </p>
        <div style="background-color: #0f172a; padding: 18px; border-radius: 12px; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #a855f7; border: 1px solid #7c3aed; margin-bottom: 25px; display: inline-block;">
            {{ $codigo }}
        </div>
        <p style="color: #64748b; font-size: 12px; margin-bottom: 0;">
            Este código caducará en 15 minutos. Si no solicitaste este restablecimiento, ignora este mensaje.
        </p>
    </div>
</body>
</html>
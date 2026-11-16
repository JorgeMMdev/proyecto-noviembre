<?php

// QUE ME DA TOC EL SUBRAYADO
/** @var \App\Modelo\Usuario $usuario */
/** @var string $codigo */
?>

<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; color: #1c1530;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e4def2; border-radius: 12px;">
        <h2 style="color: #5b21b6;">Código de verificación</h2>
        <p>Hola <strong><?= htmlspecialchars($usuario->getNombre()) ?></strong>,</p>
        <p>Usa este código para terminar de iniciar sesión en Banco de Horas:</p>
        <p style="font-size: 32px; font-weight: bold; letter-spacing: 8px; text-align: center;
                  background: #f1ebff; color: #5b21b6; padding: 16px; border-radius: 12px;">
            <?= htmlspecialchars($codigo) ?>
        </p>
        <p>El código caduca en 5 minutos. Si no has intentado iniciar sesión, ignora este correo.</p>
    </div>
</body>
</html>
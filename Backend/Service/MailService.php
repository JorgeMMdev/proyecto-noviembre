<?php

namespace App\Service;

use App\Modelo\Usuario;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    public function __construct(private array $smtpConfig) {}

    // Crea el servicio con la configuración de las variables de entorno de Docker
    public static function desdeEntorno(): self
    {
        return new self([
            'host'       => getenv('MAIL_HOST'),
            'port'       => (int) getenv('MAIL_PORT'),
            'username'   => '',
            'password'   => '',
            'encryption' => '',
            'from_email' => 'no-reply@bancohoras.com',
            'from_name'  => 'Banco de Horas',
        ]);
    }

    private function renderTemplate(string $templatePath, array $data): string
    {
        extract($data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->smtpConfig['host'];
        $mail->Port = $this->smtpConfig['port'];

        $mail->SMTPAuth = !empty($this->smtpConfig['username']);
        if ($mail->SMTPAuth) {
            $mail->Username = $this->smtpConfig['username'];
            $mail->Password = $this->smtpConfig['password'];
        }

        if (!empty($this->smtpConfig['encryption'])) {
            $mail->SMTPSecure = $this->smtpConfig['encryption'];
        } else {
            $mail->SMTPSecure  = false;
            $mail->SMTPAutoTLS = false;
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($this->smtpConfig['from_email'], $this->smtpConfig['from_name']);
        $mail->isHTML(true);

        return $mail;
    }

    public function enviarCodigoLogin(Usuario $usuario, string $codigo): bool
    {
        try {
            $body = $this->renderTemplate(__DIR__ . '/../Templates/emails/codigo_login.php', [
                'usuario' => $usuario,
                'codigo'  => $codigo,
            ]);

            $mail = $this->createMailer();
            $mail->addAddress($usuario->getCorreo(), $usuario->getNombre());
            $mail->Subject = 'Tu código de verificación';
            $mail->Body    = $body;
            $mail->AltBody = "Tu código de verificación es: $codigo. Caduca en 5 minutos.";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar el código de login: " . $e->getMessage());
            return false;
        }
    }
}
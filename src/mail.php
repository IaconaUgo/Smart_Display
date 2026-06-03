<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../vendor/autoload.php";

function send_verification_email(
    string $email,
    string $token
): bool {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = "smtp.gmail.com";

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV["SMTP_USER"];

        $mail->Password = $_ENV["SMTP_PASSWORD"];

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        $mail->setFrom(
            $_ENV["SMTP_USER"],
            "SmartDisplay"
        );

        $mail->addAddress($email);

        $url =
            "https://smart-display-web.vercel.app/verification?token="
            . $token;

        $mail->isHTML(true);

        $mail->CharSet = "UTF-8";

        $mail->Subject = "Vérification de votre compte";

        $mail->Body = "
            <h2>Bienvenue sur SmartDisplay</h2>

            <p>
                Cliquez sur le lien ci-dessous pour vérifier votre compte :
            </p>

            <p>
                <a href='$url'>
                    Vérifier mon compte
                </a>
            </p>
        ";

        $mail->AltBody =
            "Vérifiez votre compte : " . $url;

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log("PHPMailer ERROR: " . $mail->ErrorInfo);
	error_log("Exception: " . $e->getMessage());
	return false;
    }

}

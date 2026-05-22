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

        $mail->Username = "tomspns06@gmail.com";

        $mail->Password = "unwj jlfo ukfh cvwe";

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        $mail->setFrom(
            "tomspns06@gmail.com",
            "SmartDisplay"
        );

        $mail->addAddress($email);

        $url =
            "http://localhost:3000/verification?token="
            . $token;

        $mail->isHTML(true);

        $mail->Subject = "Verification de votre compte";

        $mail->Body = "
            <h2>Bienvenue sur SmartDisplay</h2>

            <p>
                Cliquez sur le lien ci-dessous
                pour verifier votre compte :
            </p>

            <a href='$url'>
                Verifier mon compte
            </a>
        ";

        $mail->send();

        return true;

    } catch (Exception $e) {

        return false;

    }

}
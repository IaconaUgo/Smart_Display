<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../vendor/autoload.php";

function send_announcement_email(
    string $email,
    string $titre,
    string $message,
    string $type,
    int $idContenu
): bool {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;

        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASSWORD"];

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        $mail->setFrom(
            $_ENV["SMTP_USER"],
            "SmartDisplay"
        );

        $mail->addAddress($email);

        $url =
            "https://smart-display-web.vercel.app";

        switch ($type) {

            case "actualite":
                $url .= "/actualites/" . $idContenu;
                $subject =
                    "📰 Nouvelle actualité";
                break;

            case "offre":
                $url .= "/offres/" . $idContenu;
                $subject =
                    "💼 Nouvelle offre";
                break;

            case "evenement":
                $url .= "/evenements/" . $idContenu;
                $subject =
                    "🎉 Nouvel événement";
                break;

            default:
                $subject =
                    "Nouvelle annonce";
        }

        $mail->isHTML(true);

        $mail->CharSet = "UTF-8";

        $mail->Subject = $subject;

        $mail->Body = "
            <h2>$titre</h2>

            <p>$message</p>

            <br>

            <a href='$url'>
                Voir l'annonce
            </a>
        ";

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            "MAIL ANNONCE ERROR : "
            . $e->getMessage()
        );

        return false;
    }
}
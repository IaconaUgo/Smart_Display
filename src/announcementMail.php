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
                    "📢 Nouvelle annonce";

                break;
        }

        $safeTitre =
            htmlspecialchars(
                $titre,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeMessage =
            nl2br(
                htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                )
            );

        $mail->isHTML(true);

        $mail->CharSet = "UTF-8";

        $mail->Subject = $subject;

        $mail->Body = "
            <div style='font-family: Arial, sans-serif;'>

                <h2>{$safeTitre}</h2>

                <p>{$safeMessage}</p>

                <br>

                <a
                    href='{$url}'
                    style='
                        background:#2563eb;
                        color:white;
                        padding:12px 20px;
                        text-decoration:none;
                        border-radius:8px;
                        display:inline-block;
                    '
                >
                    Voir l'annonce
                </a>

                <p style='margin-top:20px;color:#666;'>

                    Cet email a été envoyé automatiquement
                    par SmartDisplay.

                </p>

            </div>
        ";

        $mail->AltBody =
            $titre .
            "\n\n" .
            $message .
            "\n\n" .
            $url;

        $mail->send();

        error_log(
            "MAIL ANNONCE ENVOYE A : "
            . $email
        );

        return true;

    } catch (Exception $e) {

        error_log(
            "MAIL ANNONCE ERROR : "
            . $e->getMessage()
        );

        return false;
    }
}
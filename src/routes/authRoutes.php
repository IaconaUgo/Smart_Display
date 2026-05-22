<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../crypto.php";
require_once __DIR__ . "/../auth.php";

return function($app) {

    // ===============================
    // API STATUS
    // ===============================

    $app->get("/", function(Request $req, Response $res) {

        $res->getBody()->write(json_encode([
            "ok" => true,
            "service" => "smartdisplay-api"
        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // REGISTER
    // ===============================

    $app->post("/auth/register", function(
        Request $req,
        Response $res
    ) {

        $body = $req->getParsedBody() ?? [];

        $nom = trim($body["nom"] ?? "");
        $prenom = trim($body["prenom"] ?? "");
        $email = strtolower(trim($body["email"] ?? ""));
        $password = trim($body["password"] ?? "");

        $telephone =
            trim($body["telephone"] ?? "");

        $dateNaissance =
            trim($body["dateNaissance"] ?? "");

        if (
            !$nom ||
            !$prenom ||
            !$email ||
            !$password
        ) {

            $res->getBody()->write(json_encode([
                "error" => "Champs manquants"
            ]));

            return $res
                ->withHeader(
                    "Content-Type",
                    "application/json"
                )
                ->withStatus(400);

        }

        // Validation email

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $res->getBody()->write(json_encode([
                "error" => "Email invalide"
            ]));

            return $res
                ->withHeader(
                    "Content-Type",
                    "application/json"
                )
                ->withStatus(400);

        }

        $pdo = db();

        // Email déjà utilisé

        $check = $pdo->prepare("
            SELECT id_user
            FROM users
            WHERE email = ?
        ");

        $check->execute([$email]);

        if ($check->fetch()) {

            $res->getBody()->write(json_encode([
                "error" => "Email déjà utilisé"
            ]));

            return $res
                ->withHeader(
                    "Content-Type",
                    "application/json"
                )
                ->withStatus(409);

        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_ARGON2ID
        );

        // 🔥 rôle 1 = utilisateur classique

        $st = $pdo->prepare("
            INSERT INTO users
            (
                nom,
                prenom,
                email,
                mot_de_passe,
                numero_telephone,
                date_naissance,
                id_role,
                date_creation
            )
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        $st->execute([
            $nom,
            $prenom,
            $email,
            $passwordHash,
            $telephone ?: null,
            $dateNaissance ?: null
        ]);

        $res->getBody()->write(json_encode([
            "ok" => true,
            "message" => "Compte créé"
        ]));

        return $res
            ->withHeader(
                "Content-Type",
                "application/json"
            )
            ->withStatus(201);

    });

    // ===============================
    // LOGIN
    // ===============================

    $app->post("/auth/login", function(
        Request $req,
        Response $res
    ) {

        $body = $req->getParsedBody() ?? [];

        $email =
            strtolower(trim($body["email"] ?? ""));

        $password =
            trim($body["password"] ?? "");

        $pdo = db();

        $st = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = ?
        ");

        $st->execute([$email]);

        $u = $st->fetch(PDO::FETCH_ASSOC);

        if (
            !$u ||
            !password_verify(
                $password,
                $u["mot_de_passe"]
            )
        ) {

            $res->getBody()->write(json_encode([
                "error" => "Identifiants invalides"
            ]));

            return $res
                ->withHeader(
                    "Content-Type",
                    "application/json"
                )
                ->withStatus(401);

        }

        $token = issue_jwt(
            (string)$u["id_user"],
            $u["email"],
            intval($u["id_role"])
        );

        $res->getBody()->write(json_encode([

            "token" => $token,

            "user" => [

                "id_user" => $u["id_user"],
                "nom" => $u["nom"],
                "prenom" => $u["prenom"],
                "email" => $u["email"],
                "telephone" => $u["numero_telephone"],
                "date_naissance" => $u["date_naissance"],
                "id_role" => $u["id_role"]

            ]

        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // LOGOUT
    // ===============================

    $app->post("/auth/logout", function(
        Request $req,
        Response $res
    ) {

        $res->getBody()->write(json_encode([
            "ok" => true
        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // ME
    // ===============================

    $app->get("/me", function(
        Request $req,
        Response $res
    ) {

        $payload = require_auth();

        $pdo = db();

        $st = $pdo->prepare("
            SELECT
                id_user,
                nom,
                prenom,
                email,
                numero_telephone,
                date_naissance,
                id_role,
                date_creation
            FROM users
            WHERE id_user = ?
        ");

        $st->execute([
            $payload["sub"]
        ]);

        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (!$user) {

            return $res->withStatus(404);

        }

        $res->getBody()->write(json_encode([
            "user" => $user
        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

};
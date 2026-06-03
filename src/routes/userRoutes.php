<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../auth.php";

return function ($app) {

    // ===============================
    // UPDATE MY PROFILE
    // ===============================

    $app->put("/users/me", function(
        Request $req,
        Response $res
    ) {

        $payload = require_auth();

        $pdo = db();

        $body = $req->getParsedBody() ?? [];

        $st = $pdo->prepare("
            UPDATE users
            SET
                nom = ?,
                prenom = ?,
                email = ?,
                numero_telephone = ?,
                date_naissance = ?
            WHERE id_user = ?
        ");

        $idRole =
            intval($body["id_role"] ?? 1);

        if (!in_array($idRole, [1, 3])) {
            $idRole = 1;
        }

        $st->execute([

            trim($body["nom"] ?? ""),
            trim($body["prenom"] ?? ""),
            strtolower(trim($body["email"] ?? "")),
            trim($body["telephone"] ?? ""),
            trim($body["dateNaissance"] ?? ""),
            $idRole,
            $args["id"]

        ]);

        $res->getBody()->write(json_encode([
            "ok" => true,
            "message" => "Profil mis à jour"
        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // GET USERS (ADMIN)
    // ===============================

    $app->get("/users", function(
        Request $req,
        Response $res
    ) {

        require_admin();

        $pdo = db();

        $users = $pdo->query("
            SELECT
                id_user,
                nom,
                prenom,
                email,
                numero_telephone,
                date_naissance,
                id_role,
                email_verifie,
                date_creation
            FROM users
            ORDER BY id_user DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $res->getBody()->write(json_encode([
            "users" => $users
        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // GET USER BY ID (ADMIN)
    // ===============================

    $app->get("/users/{id}", function(
        Request $req,
        Response $res,
        $args
    ) {

        require_admin();

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
                email_verifie,
                date_creation
            FROM users
            WHERE id_user = ?
        ");

        $st->execute([
            $args["id"]
        ]);

        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (!$user) {

            $res->getBody()->write(json_encode([
                "error" => "Utilisateur introuvable"
            ]));

            return $res
                ->withHeader(
                    "Content-Type",
                    "application/json"
                )
                ->withStatus(404);

        }

        $res->getBody()->write(json_encode([
            "user" => $user
        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // CREATE USER (ADMIN)
    // ===============================

    $app->post("/users", function(
        Request $req,
        Response $res
    ) {

        require_admin();

        $pdo = db();

        $body = $req->getParsedBody() ?? [];

        $nom = trim($body["nom"] ?? "");
        $prenom = trim($body["prenom"] ?? "");
        $email = strtolower(trim($body["email"] ?? ""));
        $password = trim($body["password"] ?? "");

        $telephone =
            trim($body["telephone"] ?? "");

        $dateNaissance =
            trim($body["dateNaissance"] ?? "");

        $idRole =
            intval($body["id_role"] ?? 1);

        if (!in_array($idRole, [1, 3])) {
            $idRole = 1;
        }

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

        $passwordHash = password_hash(
            $password,
            PASSWORD_ARGON2ID
        );

        $st = $pdo->prepare("
            INSERT INTO users
            (
                nom,
                prenom,
                email,
                mot_de_passe,
                id_role,
                numero_telephone,
                date_naissance,
                email_verifie,
                date_creation
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, 1, NOW()
            )
        ");

        $st->execute([

            $nom,
            $prenom,
            $email,
            $passwordHash,
            $idRole,
            $telephone ?: null,
            $dateNaissance ?: null

        ]);

        $res->getBody()->write(json_encode([
            "ok" => true,
            "message" => "Utilisateur créé"
        ]));

        return $res
            ->withHeader(
                "Content-Type",
                "application/json"
            )
            ->withStatus(201);

    });

    // ===============================
    // UPDATE ROLE
    // ===============================

    $app->put("/users/{id}/role", function(
        Request $req,
        Response $res,
        $args
    ) {

        require_admin();

        $pdo = db();

        $body = $req->getParsedBody() ?? [];

        $st = $pdo->prepare("
            UPDATE users
            SET id_role = ?
            WHERE id_user = ?
        ");

        $idRole =
            intval($body["id_role"] ?? 1);

        if (!in_array($idRole, [1, 3])) {
            $idRole = 1;
        }

        $st->execute([
            $idRole,
            $args["id"]
        ]);

        $res->getBody()->write(json_encode([
            "ok" => true,
            "message" => "Rôle modifié"
        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // UPDATE USER (ADMIN)
    // ===============================

    $app->put("/users/{id}", function(
        Request $req,
        Response $res,
        $args
    ) {

        require_admin();

        $pdo = db();

        $body = $req->getParsedBody() ?? [];

        $st = $pdo->prepare("
            UPDATE users
            SET
                nom = ?,
                prenom = ?,
                email = ?,
                numero_telephone = ?,
                date_naissance = ?,
                id_role = ?
            WHERE id_user = ?
        ");

        $st->execute([

            trim($body["nom"] ?? ""),
            trim($body["prenom"] ?? ""),
            strtolower(trim($body["email"] ?? "")),
            trim($body["telephone"] ?? ""),
            trim($body["dateNaissance"] ?? ""),
            intval($body["id_role"] ?? 1),
            $args["id"]

        ]);

        $res->getBody()->write(json_encode([
            "ok" => true,
            "message" => "Utilisateur modifié"
        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // DELETE USER (ADMIN)
    // ===============================

    $app->delete("/users/{id}", function(
        Request $req,
        Response $res,
        $args
    ) {

        $payload = require_admin();

        if ((int)$payload["sub"] === (int)$args["id"]) {

            $res->getBody()->write(json_encode([
                "error" =>
                    "Vous ne pouvez pas supprimer votre propre compte"
            ]));

            return $res
                ->withHeader(
                    "Content-Type",
                    "application/json"
                )
                ->withStatus(400);

        }

        $pdo = db();

        $check = $pdo->prepare("
            SELECT id_user
            FROM users
            WHERE id_user = ?
        ");

        $check->execute([
            $args["id"]
        ]);

        if (!$check->fetch()) {

            $res->getBody()->write(json_encode([
                "error" => "Utilisateur introuvable"
            ]));

            return $res
                ->withHeader(
                    "Content-Type",
                    "application/json"
                )
                ->withStatus(404);

        }

        $st = $pdo->prepare("
            DELETE FROM users
            WHERE id_user = ?
        ");

        $st->execute([
            $args["id"]
        ]);

        $res->getBody()->write(json_encode([
            "ok" => true,
            "message" => "Utilisateur supprimé"
        ]));

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

};
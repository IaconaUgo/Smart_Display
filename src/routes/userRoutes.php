<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../auth.php";

return function ($app) {

    // ===============================
    // CREATE USER (ADMIN)
    // ===============================
    $app->post("/users", function (Request $req, Response $res) {

        $payload = require_auth();

        // 🔐 Vérification admin
        if (($payload["role"] ?? "") != 1 && ($payload["role"] ?? "") !== "admin") {

            $res->getBody()->write(json_encode([
                "error" => "Accès refusé"
            ]));

            return $res
                ->withHeader("Content-Type", "application/json")
                ->withStatus(403);
        }

        $pdo = db();

        $body = $req->getParsedBody() ?? [];

        // 🔥 Validation
        if (
            empty($body["nom"]) ||
            empty($body["prenom"]) ||
            empty($body["email"]) ||
            empty($body["password"])
        ) {

            $res->getBody()->write(json_encode([
                "error" => "Champs manquants"
            ]));

            return $res
                ->withHeader("Content-Type", "application/json")
                ->withStatus(400);
        }

        // 🔥 Vérifie si email déjà utilisé
        $check = $pdo->prepare("
            SELECT id_user
            FROM users
            WHERE email = ?
        ");

        $check->execute([
            strtolower(trim($body["email"]))
        ]);

        if ($check->fetch()) {

            $res->getBody()->write(json_encode([
                "error" => "Email déjà utilisé"
            ]));

            return $res
                ->withHeader("Content-Type", "application/json")
                ->withStatus(409);
        }

        // 🔒 Hash mot de passe
        $hash = password_hash(
            $body["password"],
            PASSWORD_ARGON2ID
        );

        // 🔥 Création utilisateur
        $st = $pdo->prepare("
            INSERT INTO users
            (
                nom,
                prenom,
                email,
                mot_de_passe,
                id_role,
                date_creation
            )
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $st->execute([
            trim($body["nom"]),
            trim($body["prenom"]),
            strtolower(trim($body["email"])),
            $hash,
            $body["id_role"] ?? 1
        ]);

        $res->getBody()->write(json_encode([
            "ok" => true,
            "message" => "Utilisateur créé"
        ]));

        return $res
            ->withHeader("Content-Type", "application/json")
            ->withStatus(201);
    });

    // ===============================
    // GET USERS
    // ===============================
    $app->get("/users", function (Request $req, Response $res) {

        $payload = require_auth();

        // 🔐 Vérification admin
        if (($payload["role"] ?? "") != 1 && ($payload["role"] ?? "") !== "admin") {

            $res->getBody()->write(json_encode([
                "error" => "Accès refusé"
            ]));

            return $res
                ->withHeader("Content-Type", "application/json")
                ->withStatus(403);
        }

        $pdo = db();

        $users = $pdo->query("
            SELECT
                id_user,
                nom,
                prenom,
                email,
                id_role,
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
    // UPDATE USER
    // ===============================
    $app->put("/users/{id}", function (
        Request $req,
        Response $res,
        $args
    ) {

        $payload = require_auth();

        if (($payload["role"] ?? "") != 1 && ($payload["role"] ?? "") !== "admin") {

            $res->getBody()->write(json_encode([
                "error" => "Accès refusé"
            ]));

            return $res
                ->withHeader("Content-Type", "application/json")
                ->withStatus(403);
        }

        $pdo = db();

        $body = $req->getParsedBody() ?? [];

        $st = $pdo->prepare("
            UPDATE users
            SET
                nom = ?,
                prenom = ?,
                email = ?,
                id_role = ?
            WHERE id_user = ?
        ");

        $st->execute([
            trim($body["nom"]),
            trim($body["prenom"]),
            strtolower(trim($body["email"])),
            $body["id_role"],
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
    // DELETE USER
    // ===============================
    $app->delete("/users/{id}", function (
        Request $req,
        Response $res,
        $args
    ) {

        $payload = require_auth();

        if (($payload["role"] ?? "") != 1 && ($payload["role"] ?? "") !== "admin") {

            $res->getBody()->write(json_encode([
                "error" => "Accès refusé"
            ]));

            return $res
                ->withHeader("Content-Type", "application/json")
                ->withStatus(403);
        }

        $pdo = db();

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

    // ===============================
    // UPDATE MY PROFILE
    // ===============================
    $app->put("/users/me", function (
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
                email = ?
            WHERE id_user = ?
        ");

        $st->execute([
            trim($body["nom"]),
            trim($body["prenom"]),
            strtolower(trim($body["email"])),
            $payload["sub"]
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
};
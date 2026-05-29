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

        $st->execute([

            trim($body["nom"] ?? ""),
            trim($body["prenom"] ?? ""),
            strtolower(trim($body["email"] ?? "")),
            trim($body["telephone"] ?? ""),
            trim($body["dateNaissance"] ?? ""),
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

        $st->execute([
            intval($body["id_role"]),
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
                id_role = ?
            WHERE id_user = ?
        ");

        $st->execute([

            trim($body["nom"] ?? ""),
            trim($body["prenom"] ?? ""),
            strtolower(trim($body["email"] ?? "")),
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

        require_admin();

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
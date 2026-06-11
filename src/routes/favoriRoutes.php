<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../auth.php";

return function ($app) {

    // ===============================
    // GET FAVORIS
    // ===============================

    $app->get("/favoris", function (
        Request $req,
        Response $res
    ) {

        $payload = require_auth();

        $pdo = db();

        $st = $pdo->prepare("
            SELECT id_contenu
            FROM favoris
            WHERE id_user = ?
        ");

        $st->execute([
            $payload["sub"]
        ]);

        $favoris = $st->fetchAll(
            PDO::FETCH_COLUMN
        );

        $res->getBody()->write(
            json_encode($favoris)
        );

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // ADD FAVORI
    // ===============================

    $app->post("/favoris/{id}", function (
        Request $req,
        Response $res,
        $args
    ) {

        $payload = require_auth();

        $pdo = db();

        $st = $pdo->prepare("
            INSERT IGNORE INTO favoris
            (
                id_user,
                id_contenu
            )
            VALUES (?, ?)
        ");

        $st->execute([
            $payload["sub"],
            $args["id"]
        ]);

        $res->getBody()->write(
            json_encode([
                "ok" => true
            ])
        );

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // DELETE FAVORI
    // ===============================

    $app->delete("/favoris/{id}", function (
        Request $req,
        Response $res,
        $args
    ) {

        $payload = require_auth();

        $pdo = db();

        $st = $pdo->prepare("
            DELETE FROM favoris
            WHERE id_user = ?
            AND id_contenu = ?
        ");

        $st->execute([
            $payload["sub"],
            $args["id"]
        ]);

        $res->getBody()->write(
            json_encode([
                "ok" => true
            ])
        );

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // FAVORIS DETAILS
    // ===============================

    $app->get("/favoris/details", function(
        Request $req,
        Response $res
    ){

        $payload = require_auth();

        $pdo = db();

        $st = $pdo->prepare("
            SELECT c.*
            FROM favoris f
            INNER JOIN contenus c
                ON c.id_contenu = f.id_contenu
            WHERE f.id_user = ?
            ORDER BY c.date_debut DESC
        ");

        $st->execute([
            $payload["sub"]
        ]);

        $favoris = $st->fetchAll(
            PDO::FETCH_ASSOC
        );

        $res->getBody()->write(
            json_encode([
                "favoris" => $favoris
            ])
        );

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

};
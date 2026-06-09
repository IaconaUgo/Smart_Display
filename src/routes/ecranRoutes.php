<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../auth.php";

return function($app) {

    // ===============================
    // GET ECRANS
    // ===============================

    $app->get("/ecrans", function(
        Request $req,
        Response $res
    ) {

        require_auth();

        $pdo = db();

        $st = $pdo->query("
            SELECT *
            FROM ecrans
            ORDER BY id_ecran DESC
        ");

        $data =
            $st->fetchAll(PDO::FETCH_ASSOC);

        $res->getBody()->write(
            json_encode($data)
        );

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // GET ECRAN BY ID
    // ===============================

    $app->get("/ecrans/{id}", function(
        Request $req,
        Response $res,
        $args
    ) {

        require_auth();

        $pdo = db();

        $st = $pdo->prepare("
            SELECT *
            FROM ecrans
            WHERE id_ecran = ?
        ");

        $st->execute([
            $args["id"]
        ]);

        $screen =
            $st->fetch(PDO::FETCH_ASSOC);

        if (!$screen) {

            $res->getBody()->write(
                json_encode([
                    "error" =>
                        "Écran introuvable"
                ])
            );

            return $res
                ->withHeader(
                    "Content-Type",
                    "application/json"
                )
                ->withStatus(404);

        }

        $res->getBody()->write(
            json_encode($screen)
        );

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // CREATE ECRAN
    // ===============================

    $app->post("/ecrans", function(
        Request $req,
        Response $res
    ) {

        require_admin();

        $body =
            $req->getParsedBody() ?? [];

        $identifiant =
            trim(
                $body["identifiant_ecran"]
                ?? ""
            );

        $adresseIp =
            trim(
                $body["adresse_ip"]
                ?? ""
            );

        $idSalle =
            intval(
                $body["id_salle"]
                ?? 1
            );

        if (!$identifiant) {

            $res->getBody()->write(
                json_encode([
                    "error" =>
                        "Identifiant requis"
                ])
            );

            return $res
                ->withHeader(
                    "Content-Type",
                    "application/json"
                )
                ->withStatus(400);

        }

        $pdo = db();

        $st = $pdo->prepare("
            INSERT INTO ecrans
            (
                identifiant_ecran,
                adresse_ip,
                statut,
                id_salle
            )
            VALUES
            (
                ?, ?, 'actif', ?
            )
        ");

        $st->execute([
            $identifiant,
            $adresseIp ?: null,
            $idSalle
        ]);

        $res->getBody()->write(
            json_encode([
                "ok" => true
            ])
        );

        return $res
            ->withHeader(
                "Content-Type",
                "application/json"
            )
            ->withStatus(201);

    });

    // ===============================
    // UPDATE ECRAN
    // ===============================

    $app->put("/ecrans/{id}", function(
        Request $req,
        Response $res,
        $args
    ) {

        require_admin();

        $body =
            $req->getParsedBody() ?? [];

        $pdo = db();

        $st = $pdo->prepare("
            UPDATE ecrans
            SET
                identifiant_ecran = ?,
                adresse_ip = ?,
                statut = ?,
                id_salle = ?
            WHERE id_ecran = ?
        ");

        $st->execute([

            trim(
                $body["identifiant_ecran"]
                ?? ""
            ),

            trim(
                $body["adresse_ip"]
                ?? ""
            ),

            trim(
                $body["statut"]
                ?? "actif"
            ),

            intval(
                $body["id_salle"]
                ?? 1
            ),

            $args["id"]

        ]);

        $res->getBody()->write(
            json_encode([
                "ok" => true,
                "message" =>
                    "Écran modifié"
            ])
        );

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

    // ===============================
    // DELETE ECRAN
    // ===============================

    $app->delete("/ecrans/{id}", function(
        Request $req,
        Response $res,
        $args
    ) {

        require_admin();

        $pdo = db();

        $st = $pdo->prepare("
            DELETE FROM ecrans
            WHERE id_ecran = ?
        ");

        $st->execute([
            $args["id"]
        ]);

        $res->getBody()->write(
            json_encode([
                "ok" => true,
                "message" =>
                    "Écran supprimé"
            ])
        );

        return $res->withHeader(
            "Content-Type",
            "application/json"
        );

    });

};
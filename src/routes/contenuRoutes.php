<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../middleware/authMiddleware.php";
require_once __DIR__ . "/../auth.php";
require_once __DIR__ . "/../announcementMail.php";

return function($app) {

  // ===============================
  // GET ALL
  // ===============================

  $app->get("/annonces", function(
    Request $req,
    Response $res
  ){

    $pdo = db();

    $data = $pdo->query("
      SELECT c.*, u.nom, u.prenom
      FROM contenus c
      JOIN users u
      ON c.id_auteur = u.id_user
      ORDER BY c.date_debut DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $res->getBody()->write(
      json_encode($data)
    );

    return $res->withHeader(
      "Content-Type",
      "application/json"
    );

  });

  // ===============================
  // GET ONE
  // ===============================

  $app->get("/annonces/{id}", function(
    Request $req,
    Response $res,
    $args
  ){

    $pdo = db();

    $st = $pdo->prepare("
      SELECT c.*, u.nom, u.prenom
      FROM contenus c
      JOIN users u
      ON c.id_auteur = u.id_user
      WHERE c.id_contenu = ?
    ");

    $st->execute([
      $args["id"]
    ]);

    $data = $st->fetch(PDO::FETCH_ASSOC);

    if (!$data) {

      $res->getBody()->write(
        json_encode([
          "error" => "Annonce introuvable"
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
      json_encode($data)
    );

    return $res->withHeader(
      "Content-Type",
      "application/json"
    );

  });

  // ===============================
  // CREATE
  // ===============================

  $app->post("/annonces", function(
    Request $req,
    Response $res
  ){

    $payload = require_auth();

    $body = $req->getParsedBody();

    $pdo = db();

    $st = $pdo->prepare("
      INSERT INTO contenus
      (
        titre,
        message,
        type,
        date_debut,
        id_auteur,
        lien
      )
      VALUES (?, ?, ?, ?, ?, ?)
    ");

    $st->execute([

      $body["titre"],
      $body["message"],
      $body["type"],
      $body["date_debut"],
      $payload["sub"],
      $body["lien"] ?? null

    ]);

    // ===============================
    // ID DE L'ANNONCE CRÉÉE
    // ===============================

    $idContenu = (int)$pdo->lastInsertId();

    // ===============================
    // ENVOI DES NOTIFICATIONS EMAIL
    // ===============================

    try {

      $users = $pdo->query("
        SELECT email
        FROM users
        WHERE email_verifie = 1
      ")->fetchAll(PDO::FETCH_ASSOC);

      foreach ($users as $user) {

        send_announcement_email(

          $user["email"],

          $body["titre"],

          $body["message"],

          $body["type"],

          $idContenu

        );

      }

    } catch (Exception $e) {

      error_log(
        "Erreur notifications annonces : "
        . $e->getMessage()
      );

    }

    $res->getBody()->write(
      json_encode([
        "ok" => true,
        "notification_count" => count($users ?? [])
      ])
    );

    return $res->withHeader(
      "Content-Type",
      "application/json"
    );

  });

  // ===============================
  // DELETE
  // ===============================

  $app->delete("/contenus/{id}", function(
    Request $req,
    Response $res,
    $args
  ){

    $payload = require_auth();

    // 🔐 ADMIN UNIQUEMENT

    if (
      ($payload["role"] ?? 0) != 3
    )
    {
        $res->getBody()->write(
          json_encode([
            "error" => "Accès refusé"
          ])
        );

        return $res
          ->withHeader(
            "Content-Type",
            "application/json"
          )
          ->withStatus(403);
    }

    $pdo = db();

    $check = $pdo->prepare("
      SELECT id_contenu
      FROM contenus
      WHERE id_contenu = ?
    ");

    $check->execute([
      $args["id"]
    ]);

    if (!$check->fetch()) {

      $res->getBody()->write(
        json_encode([
          "error" => "Contenu introuvable"
        ])
      );

      return $res
        ->withHeader(
          "Content-Type",
          "application/json"
        )
        ->withStatus(404);

    }

    $st = $pdo->prepare("
      DELETE FROM contenus
      WHERE id_contenu = ?
    ");

    $st->execute([
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

};
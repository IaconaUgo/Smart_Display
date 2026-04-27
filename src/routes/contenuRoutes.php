<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../middleware/authMiddleware.php";

return function($app) {

  // ===============================
  // 🔹 GET toutes les annonces
  // ===============================
  $app->get("/annonces", function(Request $req, Response $res){

    $pdo = db();

    $data = $pdo->query("
      SELECT c.*, u.nom, u.prenom
      FROM contenus c
      JOIN users u ON c.id_auteur = u.id_user
      ORDER BY c.date_debut DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $res->getBody()->write(json_encode($data));

    return $res->withHeader("Content-Type","application/json");
  });


  // ===============================
  // 🔹 GET une annonce
  // ===============================
  $app->get("/annonces/{id}", function(Request $req, Response $res, $args){

    $pdo = db();

    $st = $pdo->prepare("
      SELECT c.*, u.nom, u.prenom
      FROM contenus c
      JOIN users u ON c.id_auteur = u.id_user
      WHERE c.id_contenu = ?
    ");

    $st->execute([$args["id"]]);

    $data = $st->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
      $res->getBody()->write(json_encode([
        "error" => "Annonce introuvable"
      ]));
      return $res
        ->withHeader("Content-Type","application/json")
        ->withStatus(404);
    }

    $res->getBody()->write(json_encode($data));

    return $res->withHeader("Content-Type","application/json");
  });


  // ===============================
  // 🔹 POST créer annonce
  // ===============================
  $app->post("/annonces", function(Request $req, Response $res){

    $payload = require_auth(); // 🔒 utilisateur connecté

    $body = $req->getParsedBody() ?? [];

    $pdo = db();

    // 🔥 sécurisation
    $titre = $body["titre"] ?? "";
    $message = $body["message"] ?? "";
    $type = $body["type"] ?? "info";
    $date_debut = $body["date_debut"] ?? null;
    $date_fin = $body["date_fin"] ?? null;

    if (!$titre || !$message) {
      $res->getBody()->write(json_encode([
        "error" => "Titre et message requis"
      ]));
      return $res->withStatus(400);
    }

    $st = $pdo->prepare("
      INSERT INTO contenus
      (titre, message, type, date_debut, date_fin, id_auteur)
      VALUES (?, ?, ?, ?, ?, ?)
    ");

    $st->execute([
      $titre,
      $message,
      $type,
      $date_debut,
      $date_fin,
      $payload["sub"]
    ]);

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type","application/json");
  });


  // ===============================
  // 🔹 DELETE annonce (admin)
  // ===============================
  $app->delete("/annonces/{id}", function(Request $req, Response $res, $args){

    $payload = require_auth();

    // 🔥 admin uniquement
    if (($payload["role"] ?? null) !== "admin") {
      $res->getBody()->write(json_encode([
        "error" => "Accès refusé"
      ]));
      return $res->withStatus(403);
    }

    $pdo = db();

    $st = $pdo->prepare("
      DELETE FROM contenus WHERE id_contenu = ?
    ");

    $st->execute([$args["id"]]);

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type","application/json");
  });


  // ===============================
  // 🔹 UPDATE annonce (admin)
  // ===============================
  $app->put("/annonces/{id}", function(Request $req, Response $res, $args){

    $payload = require_auth();

    if (($payload["role"] ?? null) !== "admin") {
      $res->getBody()->write(json_encode([
        "error" => "Accès refusé"
      ]));
      return $res->withStatus(403);
    }

    $body = $req->getParsedBody() ?? [];
    $pdo = db();

    $st = $pdo->prepare("
      UPDATE contenus
      SET titre = ?, message = ?, type = ?, date_debut = ?, date_fin = ?
      WHERE id_contenu = ?
    ");

    $st->execute([
      $body["titre"] ?? "",
      $body["message"] ?? "",
      $body["type"] ?? "info",
      $body["date_debut"] ?? null,
      $body["date_fin"] ?? null,
      $args["id"]
    ]);

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type","application/json");
  });

};
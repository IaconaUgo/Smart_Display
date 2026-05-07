<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../auth.php";

return function($app) {

  // ===============================
  // GET ECRANS
  // ===============================
  $app->get("/ecrans", function(Request $req, Response $res) {

    require_auth();

    $pdo = db();

    $data = $pdo->query("
      SELECT *
      FROM ecrans
      ORDER BY id_ecran DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $res->getBody()->write(json_encode($data));

    return $res->withHeader("Content-Type", "application/json");
  });


  // ===============================
  // CREATE ECRAN
  // ===============================
  $app->post("/ecrans", function(Request $req, Response $res) {

    $payload = require_auth();

    // 🔐 ADMIN UNIQUEMENT
    if ($payload["role"] !== "admin") {

      $res->getBody()->write(json_encode([
        "error" => "Accès refusé"
      ]));

      return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(403);
    }

    $body = $req->getParsedBody() ?? [];

    $identifiant = trim($body["identifiant_ecran"] ?? "");
    $adresseIp = trim($body["adresse_ip"] ?? "");
    $idSalle = intval($body["id_salle"] ?? 1);

    if (!$identifiant) {

      $res->getBody()->write(json_encode([
        "error" => "Identifiant écran requis"
      ]));

      return $res
        ->withHeader("Content-Type", "application/json")
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
      VALUES (?, ?, 'actif', ?)
    ");

    $st->execute([
      $identifiant,
      $adresseIp ?: null,
      $idSalle
    ]);

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type", "application/json");
  });

};
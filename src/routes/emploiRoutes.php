<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../auth.php";

return function($app) {

  // ===================================
  // GET ALL EDT
  // ===================================

  $app->get("/emplois", function(Request $req, Response $res) {

    $pdo = db();

    $data = $pdo->query("
      SELECT *
      FROM emplois_du_temps
      ORDER BY classe, jour, heure_debut
    ")->fetchAll(PDO::FETCH_ASSOC);

    $res->getBody()->write(json_encode($data));

    return $res->withHeader("Content-Type", "application/json");
  });

  // ===================================
  // GET PAR CLASSE
  // ===================================

  $app->get("/emplois/{classe}", function(Request $req, Response $res, $args) {

    $pdo = db();

    $st = $pdo->prepare("
      SELECT *
      FROM emplois_du_temps
      WHERE classe = ?
      ORDER BY jour, heure_debut
    ");

    $st->execute([
      strtoupper($args["classe"])
    ]);

    $data = $st->fetchAll(PDO::FETCH_ASSOC);

    $res->getBody()->write(json_encode($data));

    return $res->withHeader("Content-Type", "application/json");
  });

  // ===================================
  // CREATE EDT
  // ===================================

  $app->post("/emplois", function(Request $req, Response $res) {

    $payload = require_auth();

    if ($payload["role"] != 1 && $payload["role"] != "admin") {

      $res->getBody()->write(json_encode([
        "error" => "Accès refusé"
      ]));

      return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(403);
    }

    $body = $req->getParsedBody();

    $pdo = db();

    $st = $pdo->prepare("
      INSERT INTO emplois_du_temps
      (
        classe,
        jour,
        heure_debut,
        heure_fin,
        matiere,
        professeur,
        salle,
        couleur
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $st->execute([
      strtoupper($body["classe"]),
      $body["jour"],
      $body["heure_debut"],
      $body["heure_fin"],
      $body["matiere"],
      $body["professeur"],
      $body["salle"],
      $body["couleur"]
    ]);

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type", "application/json");
  });

  // ===================================
  // DELETE
  // ===================================

  $app->delete("/emplois/{id}", function(Request $req, Response $res, $args) {

    $payload = require_auth();

    if ($payload["role"] != 1 && $payload["role"] != "admin") {

      return $res->withStatus(403);
    }

    $pdo = db();

    $st = $pdo->prepare("
      DELETE FROM emplois_du_temps
      WHERE id_edt = ?
    ");

    $st->execute([
      $args["id"]
    ]);

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type","application/json");
  });

};
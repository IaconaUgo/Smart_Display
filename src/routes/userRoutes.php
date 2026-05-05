<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../auth.php";

return function($app) {

  // ===============================
  // CREATE USER (ADMIN)
  // ===============================
  $app->post("/users", function(Request $req, Response $res) {

    $payload = require_auth();

    if ($payload["role"] != 1) {
      return $res->withStatus(403);
    }

    $pdo = db();
    $body = $req->getParsedBody();

    $hash = password_hash($body["password"], PASSWORD_ARGON2ID);

    $st = $pdo->prepare("
      INSERT INTO users (nom, prenom, email, mot_de_passe, id_role, date_creation)
      VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $st->execute([
      $body["nom"],
      $body["prenom"],
      strtolower($body["email"]),
      $hash,
      $body["id_role"] ?? 1
    ]);

    $res->getBody()->write(json_encode(["ok"=>true]));
    return $res->withHeader("Content-Type","application/json");
  });

  // ===============================
  // GET USERS
  // ===============================
  $app->get("/users", function(Request $req, Response $res) {

    $payload = require_auth();

    if ($payload["role"] != 1) {
      return $res->withStatus(403);
    }

    $pdo = db();

    $users = $pdo->query("
      SELECT id_user, nom, prenom, email, id_role
      FROM users
    ")->fetchAll(PDO::FETCH_ASSOC);

    $res->getBody()->write(json_encode(["users"=>$users]));
    return $res->withHeader("Content-Type","application/json");
  });

};
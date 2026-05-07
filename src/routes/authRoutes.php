<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../crypto.php";
require_once __DIR__ . "/../auth.php";

return function($app) {

  $app->get("/", function(Request $req, Response $res) {
    $res->getBody()->write(json_encode([
      "ok" => true,
      "service" => "smartdisplay-api"
    ]));
    return $res->withHeader("Content-Type","application/json");
  });

  $app->post("/auth/register", function(Request $req, Response $res) {

    $body = $req->getParsedBody() ?? [];

    $email = strtolower(trim($body["email"] ?? ""));
    $password = $body["password"] ?? "";

    if (!$email || !$password) {
      return $res->withStatus(400);
    }

    $pdo = db();

    $check = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
      return $res->withStatus(409);
    }

    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);

    $st = $pdo->prepare("
      INSERT INTO users (nom, prenom, email, mot_de_passe, id_role, date_creation)
      VALUES (?, ?, ?, ?, 1, NOW())
    ");

    $st->execute([
      $body["nom"] ?? "",
      $body["prenom"] ?? "",
      $email,
      $passwordHash
    ]);

    $res->getBody()->write(json_encode(["ok"=>true]));
    return $res->withHeader("Content-Type","application/json");
  });

  // 🔥 LOGIN FIX
  $app->post("/auth/login", function(Request $req, Response $res) {

    $body = $req->getParsedBody() ?? [];
    $pdo = db();

    $st = $pdo->prepare("
      SELECT *
      FROM users
      WHERE email = ?
    ");

    $st->execute([
      strtolower(trim($body["email"] ?? ""))
    ]);

    $u = $st->fetch(PDO::FETCH_ASSOC);

    if (!$u || !password_verify($body["password"] ?? "", $u["mot_de_passe"])) {
      return $res->withStatus(401);
    }

    // ✅ FIX ROLE
    $token = issue_jwt(
      (string)$u["id_user"],
      $u["email"],
      $u["id_role"]
    );

    $res->getBody()->write(json_encode([
      "token" => $token,
      "user" => [
        "id_user" => $u["id_user"],
        "nom" => $u["nom"],
        "prenom" => $u["prenom"],
        "email" => $u["email"],
        "id_role" => $u["id_role"]
      ]
    ]));

    return $res->withHeader("Content-Type","application/json");
  });

  $app->post("/auth/logout", function(Request $req, Response $res) {
    $res->getBody()->write(json_encode(["ok"=>true]));
    return $res->withHeader("Content-Type","application/json");
  });

  $app->get("/me", function(Request $req, Response $res) {

    $payload = require_auth();
    $pdo = db();

    $st = $pdo->prepare("
      SELECT id_user, nom, prenom, email, id_role
      FROM users
      WHERE id_user = ?
    ");

    $st->execute([$payload["sub"]]);
    $user = $st->fetch(PDO::FETCH_ASSOC);

    if (!$user) return $res->withStatus(404);

    $res->getBody()->write(json_encode(["user"=>$user]));
    return $res->withHeader("Content-Type","application/json");
  });

};
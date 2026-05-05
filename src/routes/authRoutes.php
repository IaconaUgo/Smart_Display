<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../crypto.php";
require_once __DIR__ . "/../auth.php";

return function($app) {

  // ===============================
  // TEST API
  // ===============================
  $app->get("/", function(Request $req, Response $res) {

    $res->getBody()->write(json_encode([
      "ok" => true,
      "service" => "smartdisplay-api"
    ]));

    return $res->withHeader("Content-Type","application/json");
  });

  // ===============================
  // REGISTER
  // ===============================
  $app->post("/auth/register", function(Request $req, Response $res) {

    $body = $req->getParsedBody() ?? [];

    $email = strtolower(trim($body["email"] ?? ""));
    $password = $body["password"] ?? "";

    if (!$email || !$password) {
      $res->getBody()->write(json_encode([
        "error" => "Email et mot de passe requis"
      ]));

      return $res->withHeader("Content-Type","application/json")->withStatus(400);
    }

    $pdo = db();

    // 🔥 vérifie si déjà existant
    $check = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
      $res->getBody()->write(json_encode([
        "error" => "Email déjà utilisé"
      ]));

      return $res->withHeader("Content-Type","application/json")->withStatus(409);
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

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type","application/json")->withStatus(201);
  });

  // ===============================
  // LOGIN (JWT)
  // ===============================
  $app->post("/auth/login", function(Request $req, Response $res) {

    $body = $req->getParsedBody() ?? [];
    $pdo = db();

    $st = $pdo->prepare("
      SELECT u.*, r.nom_role
      FROM users u
      JOIN roles r ON u.id_role = r.id_role
      WHERE email = ?
    ");

    $st->execute([
      strtolower(trim($body["email"] ?? ""))
    ]);

    $u = $st->fetch(PDO::FETCH_ASSOC);

    if (!$u || !password_verify($body["password"] ?? "", $u["mot_de_passe"])) {
      $res->getBody()->write(json_encode([
        "error" => "Identifiants invalides"
      ]));

      return $res->withHeader("Content-Type","application/json")->withStatus(401);
    }

    $token = issue_jwt(
      (string)$u["id_user"],
      $u["email"],
      $u["nom_role"]
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

  // ===============================
  // LOGOUT
  // ===============================
  $app->post("/auth/logout", function(Request $req, Response $res) {

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type","application/json");
  });

  // ===============================
  // ME
  // ===============================
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

    if (!$user) {
      $res->getBody()->write(json_encode([
        "error" => "Utilisateur introuvable"
      ]));

      return $res->withHeader("Content-Type","application/json")->withStatus(404);
    }

    $res->getBody()->write(json_encode([
      "user" => $user
    ]));

    return $res->withHeader("Content-Type","application/json");
  });

};
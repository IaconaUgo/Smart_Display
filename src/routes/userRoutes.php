<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../auth.php";

return function($app) {

  // ===============================
  // MODIFIER MON PROFIL
  // ===============================

  $app->put("/users/me", function(Request $req, Response $res) {

    $payload = require_auth();
    $pdo = db();

    $body = $req->getParsedBody() ?? [];

    $nom = trim($body["nom"] ?? "");
    $prenom = trim($body["prenom"] ?? "");
    $email = strtolower(trim($body["email"] ?? ""));
    $telephone = trim($body["telephone"] ?? "");
    $dateNaissance = trim($body["dateNaissance"] ?? "");

    $st = $pdo->prepare("
      UPDATE users
      SET nom = ?, prenom = ?, email = ?, numero_telephone = ?, date_naissance = ?
      WHERE id_user = ?
    ");

    $st->execute([
      $nom,
      $prenom,
      $email,
      $telephone ?: null,
      $dateNaissance ?: null,
      $payload["sub"]
    ]);

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type","application/json");

  });


  $app->put("/users/{id}", function(Request $req, Response $res, $args) {

    $payload = require_auth();

    if ($payload["role"] !== "admin") {
      $res->getBody()->write(json_encode(["error"=>"Accès refusé"]));
      return $res->withHeader("Content-Type","application/json")->withStatus(403);
    }

    $pdo = db();
    $body = $req->getParsedBody() ?? [];

    $st = $pdo->prepare("
      UPDATE users
      SET nom=?, prenom=?, email=?, id_role=?
      WHERE id_user=?
    ");

    $st->execute([
      $body["nom"],
      $body["prenom"],
      $body["email"],
      $body["id_role"],
      $args["id"]
    ]);

    $res->getBody()->write(json_encode(["ok"=>true]));
    return $res->withHeader("Content-Type","application/json");

  });


  // ===============================
  // CHANGER MOT DE PASSE
  // ===============================

  $app->put("/users/me/password", function(Request $req, Response $res) {

    $payload = require_auth();
    $pdo = db();

    $body = $req->getParsedBody() ?? [];

    $current = $body["currentPassword"] ?? "";
    $new = $body["newPassword"] ?? "";

    $st = $pdo->prepare("SELECT mot_de_passe FROM users WHERE id_user = ?");
    $st->execute([$payload["sub"]]);

    $user = $st->fetch();

    if (!$user || !password_verify($current, $user["mot_de_passe"])) {

      $res->getBody()->write(json_encode([
        "error" => "Mot de passe actuel incorrect"
      ]));

      return $res
        ->withHeader("Content-Type","application/json")
        ->withStatus(401);

    }

    $hash = password_hash($new, PASSWORD_ARGON2ID);

    $st = $pdo->prepare("
      UPDATE users
      SET mot_de_passe = ?
      WHERE id_user = ?
    ");

    $st->execute([$hash, $payload["sub"]]);

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type","application/json");

  });


  // ===============================
  // LISTE UTILISATEURS (ADMIN)
  // ===============================

  $app->get("/users", function(Request $req, Response $res) {

    $payload = require_auth();

    if ($payload["role"] !== "admin") {

      $res->getBody()->write(json_encode([
        "error" => "Accès refusé"
      ]));

      return $res
        ->withHeader("Content-Type","application/json")
        ->withStatus(403);
    }

    $pdo = db();

    $users = $pdo->query("
      SELECT id_user, nom, prenom, email, id_role, date_creation
      FROM users
    ")->fetchAll(PDO::FETCH_ASSOC);

    $res->getBody()->write(json_encode([
      "users" => $users
    ]));

    return $res->withHeader("Content-Type","application/json");

  });


  // ===============================
  // SUPPRIMER UTILISATEUR (ADMIN)
  // ===============================

  $app->delete("/users/{id}", function(Request $req, Response $res, $args) {

    $payload = require_auth();

    if ($payload["role"] !== "admin") {

      $res->getBody()->write(json_encode([
        "error" => "Accès refusé"
      ]));

      return $res
        ->withHeader("Content-Type","application/json")
        ->withStatus(403);
    }

    $pdo = db();

    $st = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
    $st->execute([$args["id"]]);

    $res->getBody()->write(json_encode([
      "ok" => true
    ]));

    return $res->withHeader("Content-Type","application/json");

  });

};
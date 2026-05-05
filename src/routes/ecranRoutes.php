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
    $data = $pdo->query("SELECT * FROM ecrans")->fetchAll();

    $res->getBody()->write(json_encode($data));
    return $res->withHeader("Content-Type","application/json");

  });

  // ===============================
  // CREATE ECRAN
  // ===============================
  $app->post("/ecrans", function(Request $req, Response $res){

    $payload = require_auth();

    if ($payload["role"] != 1) {
      return $res->withStatus(403);
    }

    $pdo = db();
    $body = $req->getParsedBody();

    $st = $pdo->prepare("
      INSERT INTO ecrans (nom, localisation)
      VALUES (?, ?)
    ");

    $st->execute([
      $body["nom"],
      $body["localisation"]
    ]);

    $res->getBody()->write(json_encode(["ok"=>true]));
    return $res->withHeader("Content-Type","application/json");
  });

};
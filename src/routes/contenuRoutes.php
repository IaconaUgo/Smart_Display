<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../middleware/authMiddleware.php";

return function($app) {

  $app->get("/contenus", function(Request $req, Response $res) {

    require_auth(); // 🔒 Doit être connecté

    $pdo = db();
    $data = $pdo->query("SELECT * FROM contenus")->fetchAll();

    $res->getBody()->write(json_encode($data));
    return $res->withHeader("Content-Type","application/json");

  });

};

$app->get("/contenus", function(Request $req, Response $res){

  $pdo = db();

  $data = $pdo->query("
    SELECT c.*, u.nom, u.prenom
    FROM contenus c
    JOIN users u ON c.id_auteur = u.id_user
    ORDER BY date_debut DESC
  ")->fetchAll(PDO::FETCH_ASSOC);

  $res->getBody()->write(json_encode([
    "contenus"=>$data
  ]));

  return $res->withHeader("Content-Type","application/json");

});

$app->post("/contenus", function(Request $req, Response $res){

  $payload = require_auth();

  $body = $req->getParsedBody();

  $pdo = db();

  $st = $pdo->prepare("
    INSERT INTO contenus
    (titre,message,type,date_debut,date_fin,id_auteur)
    VALUES (?,?,?,?,?,?)
  ");

  $st->execute([
    $body["titre"],
    $body["message"],
    $body["type"],
    $body["date_debut"],
    $body["date_fin"],
    $payload["sub"]
  ]);

  $res->getBody()->write(json_encode([
    "ok"=>true
  ]));

  return $res->withHeader("Content-Type","application/json");

});
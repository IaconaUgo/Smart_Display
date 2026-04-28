<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../middleware/authMiddleware.php";

return function($app) {

  // GET ALL
  $app->get("/annonces", function(Request $req, Response $res){
    $pdo = db();

    $data = $pdo->query("
      SELECT c.*, u.nom, u.prenom
      FROM contenus c
      JOIN users u ON c.id_auteur = u.id_user
      ORDER BY c.date_debut DESC
    ")->fetchAll();

    $res->getBody()->write(json_encode($data));
    return $res->withHeader("Content-Type","application/json");
  });

  // GET ONE
  $app->get("/annonces/{id}", function(Request $req, Response $res, $args){
    $pdo = db();

    $st = $pdo->prepare("
      SELECT c.*, u.nom, u.prenom
      FROM contenus c
      JOIN users u ON c.id_auteur = u.id_user
      WHERE c.id_contenu = ?
    ");

    $st->execute([$args["id"]]);
    $data = $st->fetch();

    if (!$data) return $res->withStatus(404);

    $res->getBody()->write(json_encode($data));
    return $res->withHeader("Content-Type","application/json");
  });

  // POST
  $app->post("/annonces", function(Request $req, Response $res){
    $payload = require_auth();
    $body = $req->getParsedBody();

    $pdo = db();

    $st = $pdo->prepare("
      INSERT INTO contenus
      (titre,message,type,date_debut,id_auteur)
      VALUES (?,?,?,?,?)
    ");

    $st->execute([
      $body["titre"],
      $body["message"],
      $body["type"],
      $body["date_debut"],
      $payload["sub"]
    ]);

    $res->getBody()->write(json_encode(["ok"=>true]));
    return $res->withHeader("Content-Type","application/json");
  });

};
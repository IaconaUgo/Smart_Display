<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../middleware/authMiddleware.php";

return function($app) {

  $app->get("/diffusions", function(Request $req, Response $res) {

    $pdo = db();
    $data = $pdo->query("SELECT * FROM diffusion")->fetchAll();

    $res->getBody()->write(json_encode($data));
    return $res->withHeader("Content-Type","application/json");

  })->add(requireRole("admin")); // 🔐 Admin

};
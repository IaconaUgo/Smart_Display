<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../middleware/authMiddleware.php";

return function($app) {

  $app->get("/roles", function(Request $req, Response $res) {

    $pdo = db();
    $roles = $pdo->query("SELECT * FROM roles")->fetchAll();

    $res->getBody()->write(json_encode($roles));
    return $res->withHeader("Content-Type","application/json");

  })->add(requireRole("admin")); // 🔐 Admin

};
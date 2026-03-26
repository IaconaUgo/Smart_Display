<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


function requireRole(string $roleName) {

  return function ($req, $handler) use ($roleName) {

    $payload = require_auth();

    if (($payload["role"] ?? null) !== $roleName) {

      $res = new \Slim\Psr7\Response();
      $res->getBody()->write(json_encode([
        "error" => "Accès refusé"
      ]));

      return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(403);
    }

    return $handler->handle($req);
  };
}
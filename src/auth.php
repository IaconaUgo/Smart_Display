<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function issue_jwt(string $userId, string $email, string $role): string {

  $payload = [
    "iss" => $_ENV["JWT_ISSUER"],
    "iat" => time(),
    "exp" => time() + intval($_ENV["JWT_TTL_SECONDS"]),
    "sub" => $userId,
    "email" => $email,
    "role" => $role
  ];

  return JWT::encode($payload, $_ENV["JWT_SECRET"], "HS256");
}

// 🔐 AUTH VIA HEADER (robuste)
function require_auth(): array {

  // 🔥 compatible Apache + Nginx
  $headers = getallheaders();

  if (!isset($headers["Authorization"])) {
    http_response_code(401);
    echo json_encode(["error" => "Token manquant"]);
    exit;
  }

  $authHeader = $headers["Authorization"];

  if (!str_starts_with($authHeader, "Bearer ")) {
    http_response_code(401);
    echo json_encode(["error" => "Format token invalide"]);
    exit;
  }

  $token = str_replace("Bearer ", "", $authHeader);

  try {
    return (array) JWT::decode(
      $token,
      new Key($_ENV["JWT_SECRET"], "HS256")
    );
  } catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token invalide"]);
    exit;
  }
}

// 🔥 ADMIN
function require_admin(): array {

  $payload = require_auth();

  if ($payload["role"] != 1) {
    http_response_code(403);
    echo json_encode(["error" => "Accès refusé"]);
    exit;
  }

  return $payload;
}
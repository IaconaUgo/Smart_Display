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

// 🔐 USER AUTH
function require_auth(): array {
  if (!isset($_COOKIE["sd_token"])) {
    http_response_code(401);
    echo json_encode(["error" => "Non authentifié"]);
    exit;
  }

  return (array) JWT::decode(
    $_COOKIE["sd_token"],
    new Key($_ENV["JWT_SECRET"], "HS256")
  );
}

// 🔥 ADMIN AUTH
function require_admin(): array {

  $payload = require_auth();

  // ⚠️ ton admin = role = 1
  if ($payload["role"] != 1) {
    http_response_code(403);
    echo json_encode(["error" => "Accès refusé (admin uniquement)"]);
    exit;
  }

  return $payload;
}
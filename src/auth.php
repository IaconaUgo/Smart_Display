<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function issue_jwt(
    string $userId,
    string $email,
    int $role
): string {

    $payload = [
        "iss" => $_ENV["JWT_ISSUER"],
        "iat" => time(),
        "exp" => time() + intval($_ENV["JWT_TTL_SECONDS"]),
        "sub" => $userId,
        "email" => $email,
        "role" => $role
    ];

    return JWT::encode(
        $payload,
        $_ENV["JWT_SECRET"],
        "HS256"
    );
}

function require_auth(): array {

    $headers = getallheaders();

    $authorization =
        $headers["Authorization"]
        ?? $headers["authorization"]
        ?? null;

    if (!$authorization) {

        http_response_code(401);

        echo json_encode([
            "error" => "Token manquant"
        ]);

        exit;
    }

    if (
        !str_starts_with(
            $authorization,
            "Bearer "
        )
    ) {

        http_response_code(401);

        echo json_encode([
            "error" => "Format token invalide"
        ]);

        exit;
    }

    $token = trim(
        str_replace(
            "Bearer ",
            "",
            $authorization
        )
    );

    try {

        return (array) JWT::decode(
            $token,
            new Key(
                $_ENV["JWT_SECRET"],
                "HS256"
            )
        );

    } catch (Exception $e) {

        http_response_code(401);

        echo json_encode([
            "error" => "Token invalide"
        ]);

        exit;

    }

}

function require_admin(): array {

    $payload = require_auth();

    if (($payload["role"] ?? 0) != 2) {

        http_response_code(403);

        echo json_encode([
            "error" => "Accès refusé"
        ]);

        exit;

    }

    return $payload;

}
<?php

require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Slim\Psr7\Response;

// 🔹 Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

// 🔹 Créer l'application Slim
$app = AppFactory::create();

// 🔹 Middleware parsing JSON
$app->addBodyParsingMiddleware();

// 🔹 Middleware CORS
$app->add(function ($request, $handler) {

    $origin = $request->getHeaderLine("Origin");

    // 🔥 Frontends autorisés
    $allowedOrigins = [
        "http://localhost:3000",
        "http://127.0.0.1:3000",
        "http://172.28.112.1:3000",
        "http://20.19.169.91:3000"
    ];

    // 🔹 Requête OPTIONS (préflight)
    if ($request->getMethod() === "OPTIONS") {

        $response = new Response();

    } else {

        $response = $handler->handle($request);

    }

    // 🔥 Autoriser uniquement les origines définies
    if (in_array($origin, $allowedOrigins)) {

        $response = $response->withHeader(
            "Access-Control-Allow-Origin",
            $origin
        );

    }

    return $response
        ->withHeader("Access-Control-Allow-Credentials", "true")
        ->withHeader(
            "Access-Control-Allow-Headers",
            "Content-Type, Authorization, X-Requested-With"
        )
        ->withHeader(
            "Access-Control-Allow-Methods",
            "GET, POST, PUT, DELETE, OPTIONS"
        );
});

// 🔹 Routes API
(require __DIR__ . "/../src/routes.php")($app);

// 🔹 Lancer l'application
$app->run();
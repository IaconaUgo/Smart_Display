<?php

require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Slim\Psr7\Response;

// 🔹 Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

// 🔹 Créer l'app Slim
$app = AppFactory::create();

// 🔹 Middleware parsing
$app->addBodyParsingMiddleware();

// 🔹 Middleware CORS FIX
$app->add(function ($request, $handler) {

    $origin = $request->getHeaderLine("Origin");

    // 🔥 Autoriser ton front UNIQUEMENT
    $allowedOrigins = [
        "http://localhost:3000"
    ];

    // Préflight
    if ($request->getMethod() === "OPTIONS") {
        $response = new Response();
    } else {
        $response = $handler->handle($request);
    }

    // 🔥 PAS de "*" avec credentials
    if (in_array($origin, $allowedOrigins)) {
        $response = $response->withHeader("Access-Control-Allow-Origin", $origin);
    }

    return $response
        ->withHeader("Access-Control-Allow-Credentials", "true")
        ->withHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Requested-With")
        ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
});

// 🔹 Routes
(require __DIR__ . "/../src/routes.php")($app);

// 🔹 Run
$app->run();
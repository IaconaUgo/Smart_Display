<?php

require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

// 🔹 Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

// 🔹 Créer l'app Slim
$app = AppFactory::create();

// 🔹 Middleware pour parser le JSON
$app->addBodyParsingMiddleware();

// 🔹 Middleware CORS (OBLIGATOIRE pour Next.js)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader("Access-Control-Allow-Origin", "http://localhost:3000")
        ->withHeader("Access-Control-Allow-Credentials", "true")
        ->withHeader("Access-Control-Allow-Headers", "Content-Type, Authorization")
        ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
});

// 🔹 Gérer les requêtes OPTIONS (preflight)
$app->options("/{routes:.*}", function ($request, $response) {
    return $response;
});

// 🔹 Charger toutes les routes
(require __DIR__ . "/../src/routes.php")($app);

// 🔹 Lancer l'application
$app->run();

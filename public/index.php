<?php

require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

// 🔹 Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

// 🔹 Créer l'app Slim
$app = AppFactory::create();

// 🔹 Middleware pour parser JSON / form-data
$app->addBodyParsingMiddleware();

// 🔹 Middleware CORS (corrigé)
$app->add(function ($request, $handler) {

    // Gestion preflight (OPTIONS)
    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
    } else {
        $response = $handler->handle($request);
    }

    return $response
        ->withHeader("Access-Control-Allow-Origin", "*") // 👈 IMPORTANT (ou ton IP)
        ->withHeader("Access-Control-Allow-Credentials", "true")
        ->withHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Requested-With")
        ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
});

// 🔹 Charger les routes
(require __DIR__ . "/../src/routes.php")($app);

// 🔹 Lancer l'app
$app->run();
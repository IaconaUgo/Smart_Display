<?php

require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Slim\Psr7\Response;

// ===============================
// ENV
// ===============================

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

// ===============================
// APP
// ===============================

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

// ===============================
// CORS
// ===============================

$app->add(function ($request, $handler) {

    $origin = $request->getHeaderLine("Origin");

    $allowed = false;

    // Local
    if (
        $origin === "http://localhost:3000" ||
        $origin === "http://127.0.0.1:3000"
    ) {
        $allowed = true;
    }

    // Tous les projets Vercel
    if (
        str_contains($origin, ".vercel.app")
    ) {
        $allowed = true;
    }

    // Préflight OPTIONS
    if ($request->getMethod() === "OPTIONS") {

        $response = new Response();

    } else {

        $response = $handler->handle($request);

    }

    if ($allowed) {

        $response = $response->withHeader(
            "Access-Control-Allow-Origin",
            $origin
        );

    }

    return $response
        ->withHeader(
            "Access-Control-Allow-Credentials",
            "true"
        )
        ->withHeader(
            "Access-Control-Allow-Headers",
            "Content-Type, Authorization, X-Requested-With"
        )
        ->withHeader(
            "Access-Control-Allow-Methods",
            "GET, POST, PUT, DELETE, OPTIONS"
        );

});

// ===============================
// ROUTES
// ===============================

(require __DIR__ . "/../src/routes.php")($app);

// ===============================
// RUN
// ===============================

$app->run();
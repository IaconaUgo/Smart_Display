<?php
require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

/**
 * CORS (optionnel pour test, mais ok)
 */
$app->add(function ($request, $handler) {
  $response = $handler->handle($request);
  return $response
    ->withHeader("Access-Control-Allow-Origin", "http://localhost:3000")
    ->withHeader("Access-Control-Allow-Credentials", "true")
    ->withHeader("Access-Control-Allow-Headers", "Content-Type, Authorization")
    ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, OPTIONS");
});

$app->options("/{routes:.*}", function ($request, $response) {
  return $response;
});

/**
 * ✅ ROUTES
 */
$routes = require __DIR__ . "/../src/routes.php";
$routes($app);

$app->run();

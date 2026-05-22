<?php

return function(\Slim\App $app) {

  $authRoutes = require __DIR__ . "/routes/authRoutes.php";
  $userRoutes = require __DIR__ . "/routes/userRoutes.php";
  $roleRoutes = require __DIR__ . "/routes/roleRoutes.php";
  $contenuRoutes = require __DIR__ . "/routes/contenuRoutes.php";
  $ecranRoutes = require __DIR__ . "/routes/ecranRoutes.php";
  $diffusionRoutes = require __DIR__ . "/routes/diffusionRoutes.php";

  // 🔥 NOUVEAU
  $emploiRoutes = require __DIR__ . "/routes/emploiRoutes.php";

  $authRoutes($app);
  $userRoutes($app);
  $roleRoutes($app);
  $contenuRoutes($app);
  $ecranRoutes($app);
  $diffusionRoutes($app);

  // 🔥 NOUVEAU
  $emploiRoutes($app);

};
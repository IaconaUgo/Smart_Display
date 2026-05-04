<?php

return function(\Slim\App $app) {

  (require_once __DIR__ . "/routes/authRoutes.php")($app);
  (require_once __DIR__ . "/routes/userRoutes.php")($app);
  (require_once __DIR__ . "/routes/roleRoutes.php")($app);
  (require_once __DIR__ . "/routes/contenuRoutes.php")($app);
  (require_once __DIR__ . "/routes/ecranRoutes.php")($app);
  (require_once __DIR__ . "/routes/diffusionRoutes.php")($app);
  (require_once __DIR__ . "/routes/annoncesRoutes.php")($app);

};
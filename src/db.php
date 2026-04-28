<?php
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $pdo = new PDO(
    "mysql:host=localhost;dbname=Smart_Display;charset=utf8mb4",
    "root",      // 👈 CHANGE ICI
    "",          // 👈 souvent vide sur Linux
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );

  return $pdo;
}
<?php
function encryption_key(): string {
  $key = hex2bin($_ENV['ENCRYPTION_KEY']);
  if (!$key || strlen($key) !== 32) {
    throw new Exception("ENCRYPTION_KEY invalide");
  }
  return $key;
}

function encrypt_string(string $plain): string {
  $iv = random_bytes(12);
  $tag = "";
  $cipher = openssl_encrypt($plain, "aes-256-gcm", encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
  return base64_encode($iv . $tag . $cipher);
}

function decrypt_string(string $payload): string {
  $data = base64_decode($payload);
  $iv = substr($data, 0, 12);
  $tag = substr($data, 12, 16);
  $cipher = substr($data, 28);
  return openssl_decrypt($cipher, "aes-256-gcm", encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
}

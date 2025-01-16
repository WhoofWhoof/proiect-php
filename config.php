<?php
  $host = 'localhost';
  $db   = 'test_site';
  $user = 'root';
  $pass = '';

  $conn = new mysqli($host, $user, $pass, $db);

  if ($conn->connect_error) {
    die("Eroare la conectare: " . $conn->connect_error);
  }

  $conn->set_charset("utf8mb4");
?>

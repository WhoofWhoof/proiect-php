<?php

function redirect($url) {
  header("Location: $url");
  exit;
}

function checkLoginStatus() {
  session_start();
  if (isset($_SESSION['user_id'])) {
    return true;
  }
  return false;
}

function isAdmin() {
  if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    return true;
  }
  return false;
}

?>

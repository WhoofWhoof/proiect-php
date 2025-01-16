<?php
  require_once 'config.php';
  require_once 'helpers.php';

  session_start();
?>

<!DOCTYPE html>
<html>

<head>
  <title>Pagina Principală</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container">
    <h1>Pagina Principală</h1>

    <?php if (!empty($_GET['msg'])): ?>
      <p class="success"><?php echo htmlspecialchars($_GET['msg']); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
    <p style="margin-bottom: 10px;">Bun venit, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>! (Rol: <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>)</p>
    <a class="button" href="logout.php">Delogare</a>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a class="button" href="admin.php">Admin Panel</a>
    <?php endif; ?>

    <!-- Link către pagina de produse -->
    <a class="button" href="products.php">Mergi la produse</a>
    <?php else: ?>
        <a class="button" href="login.php">Autentificare</a> | <a class="button" href="register.php">Înregistrare</a>
        <a class="button" href="products.php">Vezi produsele (fără cont)</a>
    <?php endif; ?>
  </div>
</body>

</html>
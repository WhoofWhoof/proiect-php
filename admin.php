<?php
  require_once 'config.php';
  require_once 'helpers.php';

  session_start();

  if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
      die("Nu aveți dreptul de a accesa această pagină.");
  }

  $sql = "SELECT id, username, email, role FROM users ORDER BY id DESC";
  $result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Admin Panel</h1>
    <p>Bun venit, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>

    <h2>Listă Utilizatori</h2>
    <table>
      <tr>
        <th>ID</th>
        <th>Nume de utilizator</th>
        <th>Email</th>
        <th>Rol</th>
      </tr>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo $row['role']; ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4">Niciun utilizator găsit.</td></tr>
      <?php endif; ?>
    </table>

    <hr>
    <div style="display: flex; justify-content: space-between;">
      <div>
        <a href="index.php">Pagina Principală</a> |
        <a href="logout.php">Delogare</a>
      </div>

      <a href="admin_orders.php" class="button">
          Vezi Comenzi
      </a>
  </div>
</div>
</body>
</html>

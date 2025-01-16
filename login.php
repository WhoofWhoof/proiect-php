<?php
require_once 'config.php';
require_once 'helpers.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Completați toate câmpurile!";
    } else {
      $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
          $_SESSION['user_id']  = $row['id'];
          $_SESSION['username'] = $row['username'];
          $_SESSION['role']     = $row['role'];

          if ($row['role'] === 'admin') {
            redirect('admin.php');

          } else {
            redirect('index.php');
          }
        } else {
            $error = "Parola incorectă.";
        }
      } else {
        $error = "Cont inexistent sau date invalide.";
      }
      $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Autentificare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Autentificare</h1>

    <?php if (!empty($_GET['msg'])): ?>
        <p class="success"><?php echo htmlspecialchars($_GET['msg']); ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="password">Parola:</label>
        <input type="password" name="password" required>

        <input type="submit" value="Loghează-te">
    </form>

    <br>
    <a href="index.php">Înapoi la pagina principală</a> |
    <a href="register.php">Nu ai cont? Înregistrează-te</a>
</div>
</body>
</html>

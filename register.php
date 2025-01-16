<?php
require_once 'config.php';
require_once 'helpers.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['username'] ?? '';
  $email    = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  if (empty($username) || empty($email) || empty($password)) {
    $error = "Completați toate câmpurile!";
  } else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Există deja un cont cu acest email.";
    } else {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
      $stmt->bind_param('sss', $username, $email, $hashedPassword);

      if ($stmt->execute()) {
        redirect('login.php?msg=Cont creat cu succes! Te poți autentifica.');
      } else {
        $error = "Eroare la crearea contului. Încearcă din nou.";
      }
    }
    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Înregistrare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Înregistrare</h1>

    <?php if (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <label for="username">Nume de utilizator:</label>
        <input type="text" name="username" required>

        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="password">Parola:</label>
        <input type="password" name="password" required>

        <input type="submit" value="Înregistrează-te">
    </form>
    <br>
    <a href="index.php">Înapoi la pagina principală</a>
</div>
</body>
</html>

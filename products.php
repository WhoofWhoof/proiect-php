<?php
require_once 'config.php';   // Conexiune la DB
require_once 'helpers.php';  // Funcții ajutătoare
session_start();

// Preluăm lista de produse din baza de date
$sql = "SELECT id, name, price FROM products";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="ro">

<head>
  <meta charset="UTF-8">
  <title>Produse</title>
  <!-- Stilul extern, dacă vrei să-l păstrezi -->
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container" style="background-color:#fdfdfd;">
    <h1 style="text-align:center; margin-bottom:20px;">Lista de Produse</h1>

    <p style="text-align:center;">
      <a href="index.php" style="margin-right:20px;">Înapoi la pagina principală</a>
      <a href="cart.php">Vezi coșul</a>
    </p>
    <hr style="margin:20px 0;">

    <!-- Afișăm produsele din baza de date -->
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
          <h2 style="margin-bottom:10px;"><?php echo htmlspecialchars($row['name']); ?></h2>
          <p style="margin-bottom:10px;">Preț:
            <strong><?php echo number_format($row['price'], 2); ?> RON</strong>
          </p>

          <!-- Formular de adăugare în coș -->
          <form action="cart.php" method="POST" style="display:inline;">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
            <input type="submit" value="Adaugă în coș"
              style="padding:8px 16px; cursor:pointer; background:#007bff; color:#fff; border:none; border-radius:4px;">
          </form>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>Nu există produse în baza de date.</p>
    <?php endif; ?>
  </div>
</body>

</html>
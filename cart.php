<?php
require_once 'config.php';
require_once 'helpers.php';
session_start();

// Inițializăm coșul dacă nu există
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// Determinăm acțiunea
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Procesăm acțiunile
if ($action === 'add') {
  $product_id = (int)($_POST['product_id'] ?? 0);

  // Verificăm dacă există produsul în DB
  $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
  $stmt->bind_param('i', $product_id);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    // Dacă produsul există, îl adăugăm în sesiune
    if (!isset($_SESSION['cart'][$product_id])) {
      $_SESSION['cart'][$product_id] = 0;
    }
    $_SESSION['cart'][$product_id] += 1;
  }
  $stmt->close();

  redirect('cart.php');
} elseif ($action === 'remove') {
  $product_id = (int)($_GET['product_id'] ?? 0);
  if (isset($_SESSION['cart'][$product_id])) {
    unset($_SESSION['cart'][$product_id]);
  }
  redirect('cart.php');
} elseif ($action === 'empty') {
  $_SESSION['cart'] = [];
  redirect('cart.php');
}

// Afișăm conținutul coșului:
?>
<!DOCTYPE html>
<html lang="ro">

<head>
  <meta charset="UTF-8">
  <title>Coș de cumpărături</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container" style="background-color:#ffffff;">
    <h1 style="text-align:center;">Coș de cumpărături</h1>

    <p style="text-align:center;">
      <a href="index.php" style="margin-right:20px;">Pagina Principală</a>
      <a href="products.php" style="margin-right:20px;">Continuă cumpărăturile</a>
      <a href="cart.php?action=empty"
        style="color:red;"
        onclick="return confirm('Ești sigur că vrei să golești coșul?');">
        Golește coșul
      </a>
    </p>
    <hr style="margin:20px 0;">

    <?php if (empty($_SESSION['cart'])): ?>
      <p style="text-align:center;">Coșul tău este gol.</p>
    <?php else: ?>
      <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#f0f0f0;">
          <th style="border:1px solid #ccc; padding:8px;">Produs</th>
          <th style="border:1px solid #ccc; padding:8px;">Preț unitar</th>
          <th style="border:1px solid #ccc; padding:8px;">Cantitate</th>
          <th style="border:1px solid #ccc; padding:8px;">Preț total</th>
          <th style="border:1px solid #ccc; padding:8px;">Acțiune</th>
        </tr>
        <?php
        $grandTotal = 0;

        // Parcurgem item-urile din coș
        foreach ($_SESSION['cart'] as $prod_id => $qty):
          // Luăm datele din DB pentru acest produs
          $stmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
          $stmt->bind_param('i', $prod_id);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result && $result->num_rows === 1) {
            $prodData = $result->fetch_assoc();
            $prodName  = $prodData['name'];
            $prodPrice = $prodData['price'];
            $total     = $prodPrice * $qty;
            $grandTotal += $total;
          } else {
            // Dacă produsul nu mai există, îl scoatem din coș
            unset($_SESSION['cart'][$prod_id]);
            continue;
          }
          $stmt->close();
        ?>
          <tr>
            <td style="border:1px solid #ccc; padding:8px;"><?php echo htmlspecialchars($prodName); ?></td>
            <td style="border:1px solid #ccc; padding:8px;"><?php echo number_format($prodPrice, 2); ?> RON</td>
            <td style="border:1px solid #ccc; padding:8px;"><?php echo $qty; ?></td>
            <td style="border:1px solid #ccc; padding:8px;"><?php echo number_format($total, 2); ?> RON</td>
            <td style="border:1px solid #ccc; padding:8px;">
              <a href="cart.php?action=remove&product_id=<?php echo $prod_id; ?>"
                style="color:#d9534f;"
                onclick="return confirm('Ștergi acest produs din coș?');">
                Șterge
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold;">
          <td colspan="3" style="border:1px solid #ccc; padding:8px;">Total general</td>
          <td colspan="2" style="border:1px solid #ccc; padding:8px;">
            <?php echo number_format($grandTotal, 2); ?> RON
          </td>
        </tr>
      </table>
        <?php if ($grandTotal > 0): ?>
          <div style="margin-top:20px;">
              <a href="checkout.php" 
                style="display:inline-block; padding:15px 30px; background:#28a745; color:#fff; 
                        text-decoration:none; font-size:18px; border-radius:5px;"
                onclick="return confirm('Ești sigur că vrei să plasezi comanda?');">
                  Plasează comanda
              </a>
          </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>

</html>
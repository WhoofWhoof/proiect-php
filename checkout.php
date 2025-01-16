<?php
require_once 'config.php';
require_once 'helpers.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  redirect('login.php?msg=Trebuie să fii logat pentru a plasa o comandă.');
}

if (empty($_SESSION['cart'])) {
  redirect('cart.php?msg=Coșul este gol, nu poți plasa comanda.');
}

$grandTotal = 0;
$cartItems = [];

foreach ($_SESSION['cart'] as $prod_id => $qty) {
  $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
  $stmt->bind_param('i', $prod_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows === 1) {
    $data = $result->fetch_assoc();
    $price = $data['price'];

    // Calculăm total parțial
    $lineTotal = $price * $qty;
    $grandTotal += $lineTotal;

    // Stocăm info pentru inserare
    $cartItems[] = [
      'product_id' => $prod_id,
      'quantity'   => $qty,
      'price'      => $price
    ];
  }
  $stmt->close();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("INSERT INTO orders (user_id, total) VALUES (?, ?)");
$stmt->bind_param('id', $userId, $grandTotal);
$stmt->execute();
$orderId = $stmt->insert_id;
$stmt->close();

$stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($cartItems as $item) {
  $stmt->bind_param(
    'iiid',
    $orderId,
    $item['product_id'],
    $item['quantity'],
    $item['price']
  );
  $stmt->execute();
}
$stmt->close();

$_SESSION['cart'] = [];

?>
<!DOCTYPE html>
<html lang="ro">

<head>
  <meta charset="UTF-8">
  <title>Comandă Plasată</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container" style="text-align:center; margin-top:50px;">
    <h1>Comanda a fost plasată cu succes!</h1>
    <p>ID Comandă: <strong><?php echo $orderId; ?></strong></p>
    <p>Total: <strong><?php echo number_format($grandTotal, 2); ?> RON</strong></p>

    <!-- Butoane de navigare -->
    <div style="margin-top:30px;">
      <a href="index.php"
        style="display:inline-block; padding:15px 30px; background:#007bff; color:#fff;
                  text-decoration:none; font-size:18px; border-radius:5px; margin-right:10px;">
        Pagina Principală
      </a>
      <a href="products.php"
        style="display:inline-block; padding:15px 30px; background:#28a745; color:#fff;
                  text-decoration:none; font-size:18px; border-radius:5px; margin-right:10px;">
        Continuă cumpărăturile
      </a>
      <a href="admin.php"
        style="display:inline-block; padding:15px 30px; background:#ffc107; color:#212529;
                  text-decoration:none; font-size:18px; border-radius:5px;">
        Admin Panel
      </a>
    </div>
  </div>
</body>

</html>
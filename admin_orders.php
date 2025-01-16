<?php
require_once 'config.php';
require_once 'helpers.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  die("Nu aveți dreptul de a accesa această pagină.");
}

$orderId = $_GET['order_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="ro">

<head>
  <meta charset="UTF-8">
  <title>Admin - Comenzi</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container">
    <h1>Administrare Comenzi</h1>

    <div style="margin: 20px 0;">
      <a href="admin.php"
        style="display:inline-block; padding:15px 30px; background:#007bff; color:#fff;
                  text-decoration:none; font-size:18px; border-radius:5px; margin-right:10px;">
        Înapoi la Admin Panel
      </a>
      <a href="index.php"
        style="display:inline-block; padding:15px 30px; background:#28a745; color:#fff;
                  text-decoration:none; font-size:18px; border-radius:5px; margin-right:10px;">
        Pagina Principală
      </a>
    </div>
    <hr>

    <?php if ($orderId): ?>

      <?php
      $stmt = $conn->prepare("
            SELECT o.id, o.user_id, o.total, o.created_at, u.username
            FROM orders AS o
            LEFT JOIN users AS u ON o.user_id = u.id
            WHERE o.id = ?
        ");
      $stmt->bind_param('i', $orderId);
      $stmt->execute();
      $orderResult = $stmt->get_result();
      $stmt->close();

      if (!$orderResult || $orderResult->num_rows === 0) {
        echo "<p>Comanda nu există.</p>";
      } else {
        $orderData = $orderResult->fetch_assoc();
      ?>
        <h2>Detalii Comandă #<?php echo $orderData['id']; ?></h2>
        <p><strong>Utilizator:</strong> <?php echo htmlspecialchars($orderData['username'] ?? 'Guest'); ?></p>
        <p><strong>Total:</strong> <?php echo number_format($orderData['total'], 2); ?> RON</p>
        <p><strong>Data:</strong> <?php echo $orderData['created_at']; ?></p>
        <hr>

        <?php
        // 2. Preluăm produsele din order_items
        $stmt = $conn->prepare("
                SELECT oi.product_id, oi.quantity, oi.price, p.name
                FROM order_items AS oi
                LEFT JOIN products AS p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $itemsResult = $stmt->get_result();
        $stmt->close();
        ?>
        <table style="width:100%; border-collapse:collapse;">
          <tr style="background:#f0f0f0;">
            <th style="border:1px solid #ccc; padding:8px;">Produs</th>
            <th style="border:1px solid #ccc; padding:8px;">Preț Unitar</th>
            <th style="border:1px solid #ccc; padding:8px;">Cantitate</th>
            <th style="border:1px solid #ccc; padding:8px;">Subtotal</th>
          </tr>
          <?php if ($itemsResult && $itemsResult->num_rows > 0): ?>
            <?php while ($item = $itemsResult->fetch_assoc()): ?>
              <tr>
                <td style="border:1px solid #ccc; padding:8px;">
                  <?php echo htmlspecialchars($item['name'] ?? 'Produs șters'); ?>
                </td>
                <td style="border:1px solid #ccc; padding:8px;">
                  <?php echo number_format($item['price'], 2); ?> RON
                </td>
                <td style="border:1px solid #ccc; padding:8px;">
                  <?php echo $item['quantity']; ?>
                </td>
                <td style="border:1px solid #ccc; padding:8px;">
                  <?php
                  $subtotal = $item['price'] * $item['quantity'];
                  echo number_format($subtotal, 2);
                  ?> RON
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" style="border:1px solid #ccc; padding:8px;">
                Nu există produse în această comandă.
              </td>
            </tr>
          <?php endif; ?>
        </table>

        <br>
        <a href="admin_orders.php" style="color:#007bff; text-decoration:none;">
          &larr; Înapoi la lista de comenzi
        </a>
      <?php
      }
      ?>
    <?php else: ?>
      <h2>Lista de Comenzi</h2>
      <?php
      $sql = "
            SELECT o.id, o.user_id, o.total, o.created_at, u.username
            FROM orders AS o
            LEFT JOIN users AS u ON o.user_id = u.id
            ORDER BY o.id DESC
        ";
      $res = $conn->query($sql);
      ?>
      <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#f0f0f0;">
          <th style="border:1px solid #ccc; padding:8px;">ID Comandă</th>
          <th style="border:1px solid #ccc; padding:8px;">Utilizator</th>
          <th style="border:1px solid #ccc; padding:8px;">Total</th>
          <th style="border:1px solid #ccc; padding:8px;">Data</th>
          <th style="border:1px solid #ccc; padding:8px;">Acțiune</th>
        </tr>
        <?php if ($res && $res->num_rows > 0): ?>
          <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
              <td style="border:1px solid #ccc; padding:8px;">
                <?php echo $row['id']; ?>
              </td>
              <td style="border:1px solid #ccc; padding:8px;">
                <?php
                echo htmlspecialchars($row['username']) ?: 'Guest';
                // (dacă user_id=0 sau nu e setat)
                ?>
              </td>
              <td style="border:1px solid #ccc; padding:8px;">
                <?php echo number_format($row['total'], 2); ?> RON
              </td>
              <td style="border:1px solid #ccc; padding:8px;">
                <?php echo $row['created_at']; ?>
              </td>
              <td style="border:1px solid #ccc; padding:8px;">
                <a href="admin_orders.php?order_id=<?php echo $row['id']; ?>"
                  style="color:#007bff;">
                  Vezi detalii
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="border:1px solid #ccc; padding:8px;">
              Nu există comenzi în baza de date.
            </td>
          </tr>
        <?php endif; ?>
      </table>
    <?php endif; ?>
  </div>
</body>

</html>
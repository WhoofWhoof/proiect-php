<?php
require_once 'config.php';
require_once 'helpers.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  die("Nu aveți dreptul de a accesa această pagină.");
}

  if (isset($_POST['upload_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
      $csvTmpName = $_FILES['csv_file']['tmp_name'];

      $handle = fopen($csvTmpName, 'r');
      if ($handle === false) {
          redirect('admin_orders.php?err=Nu s-a putut deschide fișierul CSV.');
      }

      $header = fgetcsv($handle, 1000, ',');
      $ordersData = [];

      while (($row = fgetcsv($handle, 1000, ',')) !== false) {

          if (count($row) < 5) {
              continue;
          }

          list($orderNumber, $userId, $productId, $quantity, $price) = $row;

          $orderNumber = (int)$orderNumber;
          $userId      = (int)$userId;
          $productId   = (int)$productId;
          $quantity    = (int)$quantity;
          $price       = (float)$price;

          $ordersData[$orderNumber][] = [
              'user_id'    => $userId,
              'product_id' => $productId,
              'quantity'   => $quantity,
              'price'      => $price
          ];
      }
      fclose($handle);

      $conn->begin_transaction();
      try {
          foreach ($ordersData as $orderNumber => $items) {
              if (empty($items)) {
                  continue;
              }
              $userId = $items[0]['user_id'] ?? 0;

              $orderTotal = 0.0;
              foreach ($items as $it) {
                  $orderTotal += $it['price'] * $it['quantity'];
              }

              $stmt = $conn->prepare("INSERT INTO orders (user_id, total) VALUES (?, ?)");
              $stmt->bind_param('id', $userId, $orderTotal);
              $stmt->execute();
              $orderId = $stmt->insert_id;
              $stmt->close();

              $stmtItem = $conn->prepare("
                  INSERT INTO order_items (order_id, product_id, quantity, price)
                  VALUES (?, ?, ?, ?)
              ");
              foreach ($items as $it) {
                  $stmtItem->bind_param(
                      'iiid',
                      $orderId,
                      $it['product_id'],
                      $it['quantity'],
                      $it['price']
                  );
                  $stmtItem->execute();
              }
              $stmtItem->close();
          }
          $conn->commit();

          redirect('admin_orders.php?msg=Importul s-a realizat cu succes!');
      } catch (Exception $e) {
          $conn->rollback();
          redirect('admin_orders.php?err=Eroare la import: ' . urlencode($e->getMessage()));
      }
  } else {
      redirect('admin_orders.php?err=Eroare la upload sau fișier CSV inexistent.');
  }
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

    <?php if (!empty($_GET['msg'])): ?>
        <p style="color:green;"><?php echo htmlspecialchars($_GET['msg']); ?></p>
    <?php endif; ?>
    <?php if (!empty($_GET['err'])): ?>
        <p style="color:red;"><?php echo htmlspecialchars($_GET['err']); ?></p>
    <?php endif; ?>

    <div style="margin-bottom:30px; background:#fafafa; padding:20px; border:1px solid #ddd;">
        <h3>Importă Comenzi din CSV</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required
                   style="margin:10px 0;">
            <br>
            <input type="submit" name="upload_csv" value="Importă CSV"
                   style="padding:10px 20px; font-size:16px; background:#ffc107; color:#212529;
                          border:none; border-radius:5px; cursor:pointer;">
        </form>
        <p style="margin-top:10px; font-style:italic;">
            Format așteptat: <br>
            <code>order_number,user_id,product_id,quantity,price</code>
        </p>
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
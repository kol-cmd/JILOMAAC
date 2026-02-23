<?php 
session_start();

require_once '../db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
  header("Location: ../login.php");
  exit;
}
if(!isset($_GET['id'])){
  header("Loaction: orders.php");
  exit;

}
$order_id = $_GET['id'];
$message= "";

if (isset($_POST['update_status'])){

$new_status= $_POST['status'];
$stmt= $pdo -> prepare("UPDATE orders SET status = ? where id = ? ");;
$stmt -> execute([$new_status, $order_id]);
$message = "Order status updated to <b>$new_status</b>.";
}

$stmt = $pdo-> prepare(" SELECT * FROM orders where id = ?");
$stmt->execute([$order_id]);
$order = $stmt-> fetch();

if(!$order){
die("Order not found.");
}

// 4. FETCH ORDER ITEMS (The Phones)
// We join with the 'products' table so we can get the product name and image
$sql = "SELECT order_items.*, products.name, products.image 
        FROM order_items 
        JOIN products ON order_items.product_id = products.id 
        WHERE order_items.order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?php echo $order_id; ?> Details</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 40px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .back-btn { text-decoration: none; color: #666; font-size: 0.9rem; }
        .back-btn:hover { color: #000; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .info-box h3 { margin-top: 0; color: #645bff; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .info-box p { margin: 10px 0; color: #444; line-height: 1.6; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #333; }
        img.thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }

        .status-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 40px; display: flex; align-items: center; gap: 20px; }
        select { padding: 10px; border-radius: 4px; border: 1px solid #ddd; }
        .btn-update { background: #645bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .btn-update:hover { background: #5046e5; }
        
        .success-msg { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Order #<?php echo $order['id']; ?></h1>
        <a href="orders.php" class="back-btn">← Back to Orders List</a>
    </div>

    <?php if($message): ?>
        <div class="success-msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="info-grid">
        <div class="info-box">
            <h3>Customer Info</h3>
            <p><?php echo nl2br(htmlspecialchars($order['customer_name'])); ?></p>
            <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
        </div>

        <div class="info-box">
            <h3>Payment Info</h3>
            <p><strong>Total Amount:</strong> <span style="font-size: 1.5rem; font-weight: bold; color: #28a745;">₦<?php echo number_format($order['total_amount']); ?></span></p>
            <p><strong>Current Status:</strong> <span style="text-transform: uppercase; font-weight: bold;"><?php echo $order['status']; ?></span></p>
        </div>
    </div>

    <h3>Items Ordered</h3>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Product</th>
                <th>Price (At Purchase)</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td>
                    <?php if($item['image']): ?>
                        <img src="../assets/images/<?php echo htmlspecialchars($item['image']); ?>" class="thumb">
                    <?php else: ?>
                        <span>No Image</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td>₦<?php echo number_format($item['price_at_purchase']); ?></td>
                <td>x<?php echo $item['quantity']; ?></td>
                <td>₦<?php echo number_format($item['price_at_purchase'] * $item['quantity']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="status-form">
        <strong style="font-size: 1.1rem;">Update Order Status:</strong>
        <form method="POST">
            <select name="status">
                <option value="pending" <?php if($order['status']=='pending') echo 'selected'; ?>>Pending</option>
                <option value="processing" <?php if($order['status']=='processing') echo 'selected'; ?>>Processing</option>
                <option value="shipped" <?php if($order['status']=='shipped') echo 'selected'; ?>>Shipped</option>
                <option value="delivered" <?php if($order['status']=='delivered') echo 'selected'; ?>>Delivered</option>
                <option value="cancelled" <?php if($order['status']=='cancelled') echo 'selected'; ?>>Cancelled</option>
            </select>
            <button type="submit" name="update_status" class="btn-update">Update Status</button>
        </form>
    </div>

</div>

</body>
</html>
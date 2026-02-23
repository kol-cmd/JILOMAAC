<?php
// Connect to database
require_once 'db.php';

// If the button is clicked, create a Fake Order
if (isset($_POST['simulate_payment'])) {
    
    // 1. Define fake order details (what Paystack usually sends back)
    $amount = $_POST['amount'];
    $status = 'completed'; // We assume payment was successful
    
    // 2. Insert into Database (This is the critical part!)
    $sql = "INSERT INTO orders (total_price, status, created_at) VALUES (?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    
    if($stmt->execute([$amount, $status])) {
        $message = "✅ Success! Payment simulated. Order saved to DB.";
        $order_id = $pdo->lastInsertId();
        
    } else {
        $message = "❌ Error: Could not save order.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Checkout (Bypass)</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 80%; margin-bottom: 10px; font-size: 1.2rem; }
        button { background: #22c55e; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; width: 100%; }
        button:hover { background: #16a34a; }
        .msg { margin-bottom: 15px; padding: 10px; border-radius: 6px; }
        .success { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

    <div class="card">
        <h2>🛍️ Test Checkout</h2>
        <p>Enter an amount to simulate a sale without Paystack.</p>
        
        <?php if(isset($message)): ?>
            <div class="msg success"><?php echo $message; ?></div>
            <p>Check your <a href="admin/dashboard.php">Admin Dashboard</a> now.</p>
        <?php endif; ?>

        <form method="POST">
            <label>Total Price (₦):</label><br>
            <input type="number" name="amount" value="50000"><br><br>
            <button type="submit" name="simulate_payment">✅ Pay Now (Simulate)</button>
        </form>
    </div>

</body>
</html>
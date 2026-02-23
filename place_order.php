<?php
session_start();
require_once 'db.php'; 

// 1. Validate Request & Cart
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: shop.php"); exit;
}
if (empty($_SESSION['cart'])) {
    header("Location: cart.php"); exit;
}

// 2. Collect Data
$user_id = $_SESSION['user_id'] ?? null; 
$name    = $_POST['customer_name'] ?? 'Guest';
$phone   = $_POST['customer_phone'] ?? '';
$email   = $_POST['customer_email'] ?? '';
$address = $_POST['customer_address'] ?? '';
$total   = $_POST['total_amount'] ?? 0;
$ref     = $_POST['payment_reference'] ?? 'PAY-' . time(); 

// 3. Start Transaction
try {
    $pdo->beginTransaction();

    // --- A. INSERT ORDER ---
    $sql = "INSERT INTO orders (user_id, customer_name, customer_phone, customer_email, customer_address, total_amount, status, payment_ref, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'completed', ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $name, $phone, $email, $address, $total, $ref]);
    
    $order_id = $pdo->lastInsertId();

    // --- B. PREPARE EMAIL HTML CONTENT ---
    $email_rows = ""; 

    // --- C. PROCESS ITEMS & DEDUCT STOCK ---
    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?)");
    $stmt_master_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
    $stmt_variant_stock = $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");

    foreach ($_SESSION['cart'] as $key => $item) {
        $pid = $item['product_id'] ?? $key; 
        $vid = $item['variant_id'] ?? null; 
        $qty = $item['quantity'] ?? 1;
        $price = $item['price'] ?? 0;
        $prod_name = $item['name'] ?? "Product #$pid";
        $options = $item['options'] ?? ''; 

        // 1. Save to Order Items
        $stmt_item->execute([$order_id, $pid, $vid, $qty, $price]);

        // 2. Deduct Master Stock
        $stmt_master_stock->execute([$qty, $pid]);

        // 3. Deduct Variant Stock
        if ($vid) {
            $stmt_variant_stock->execute([$qty, $vid]);
        }

        // 4. Build Table Rows for the Emails
        $subtotal = number_format($price * $qty);
        $email_rows .= "
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid #eee;'>
                    <strong>$prod_name</strong><br>
                    <span style='font-size: 12px; color: #888;'>$options</span>
                </td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>x$qty</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: right;'>₦$subtotal</td>
            </tr>";
    }

    $pdo->commit();
    
    // ==========================================
    // 4. SEND PROFESSIONAL HTML EMAILS
    // ==========================================
    
    $manager_email = "aniemanuelkolise@gmail.com"; 
    $site_name = "Jilomaac";
    $from_email = "anikolise@gmail.com"; // Change to your actual domain email

    // Common Email Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $site_name <$from_email>" . "\r\n";

    // ------------------------------------------
    // EMAIL 1: TO THE ADMIN (You)
    // ------------------------------------------
    $admin_subject = "🚨 PAYMENT RECEIVED - Order #$order_id (₦" . number_format($total) . ")";
    $admin_message = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
            <div style='background: #111; color: white; padding: 20px; text-align: center; font-size: 22px; font-weight: bold;'>
                💰 New Paid Order Received!
            </div>
            <div style='padding: 20px;'>
                <p>A new order has been paid for and requires processing.</p>
                <p><strong>Order ID:</strong> #$order_id</p>
                <p><strong>Payment Ref:</strong> $ref</p>
                <p><strong>Total Paid:</strong> <span style='color:#2ecc71; font-weight:bold; font-size:18px;'>₦" . number_format($total) . "</span></p>
                <hr style='border: 0; border-bottom: 1px solid #eee; margin: 20px 0;'>
                
                <h3 style='color: #645bff;'>Customer Details:</h3>
                <p><strong>Name:</strong> $name<br>
                   <strong>Phone:</strong> $phone<br>
                   <strong>Email:</strong> $email<br>
                   <strong>Address:</strong> $address</p>
                <hr style='border: 0; border-bottom: 1px solid #eee; margin: 20px 0;'>

                <h3 style='color: #645bff;'>Order Items:</h3>
                <table width='100%' style='border-collapse: collapse; margin-bottom: 20px;'>
                    $email_rows
                </table>
                <div style='text-align: center;'>
                    <a href='https://jilomaac.com/admin/orders.php' style='background: #645bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>View in Dashboard</a>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
    @mail($manager_email, $admin_subject, $admin_message, $headers);

    // ------------------------------------------
    // EMAIL 2: TO THE CUSTOMER
    // ------------------------------------------
    if (!empty($email)) {
        $customer_subject = "Thank you for your order from $site_name! (#$order_id)";
        $customer_message = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
                <div style='background: #645bff; color: white; padding: 30px; text-align: center;'>
                    <h1 style='margin: 0; font-size: 28px;'>Thank You, $name!</h1>
                    <p style='margin-top: 5px; opacity: 0.9;'>We have received your payment and your order is being processed.</p>
                </div>
                <div style='padding: 30px;'>
                    <p>Hi $name,</p>
                    <p>Thank you for shopping with <strong>Jilomaac</strong>. Your payment of <strong>₦" . number_format($total) . "</strong> was successful (Ref: $ref).</p>
                    
                    <h3 style='border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-top: 30px;'>Order Summary (#$order_id)</h3>
                    <table width='100%' style='border-collapse: collapse; margin-bottom: 20px;'>
                        $email_rows
                    </table>
                    
                    <table width='100%' style='border-collapse: collapse;'>
                        <tr>
                            <td style='text-align: right; padding: 10px; font-size: 18px;'><strong>Total Paid:</strong></td>
                            <td style='text-align: right; padding: 10px; font-size: 18px; color: #645bff;'><strong>₦" . number_format($total) . "</strong></td>
                        </tr>
                    </table>

                    <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 30px; border-left: 4px solid #645bff;'>
                        <strong>Shipping Address:</strong><br>
                        $address<br>
                        Phone: $phone
                    </div>

                    <p style='margin-top: 30px; font-size: 14px; color: #666; text-align: center;'>
                        If you have any questions, reply to this email or contact us at support@jilomaac.com.
                    </p>
                </div>
            </div>
        </body>
        </html>";

        @mail($email, $customer_subject, $customer_message, $headers);
    }

    // ==========================================
    // FINISH & CLEAR CART
    // ==========================================

    unset($_SESSION['cart']);
    header("Location: success.php?oid=$order_id");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("<h1>❌ SQL Error:</h1><p>" . $e->getMessage() . "</p>");
}
?>
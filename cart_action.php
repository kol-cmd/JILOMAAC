<?php
session_start();
require_once 'db.php'; 

// 1. Initialize Cart
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

// ==========================================
// LOGIC: ADD TO CART
// ==========================================
if (isset($_POST['add_to_cart'])) {
    
    $product_id = $_POST['product_id'];
    $variant_id = $_POST['variant_id'] ?? null; // Required now
    $qty_to_add = (int)$_POST['quantity'];
    if ($qty_to_add < 1) $qty_to_add = 1;

    // 1. Fetch Parent Info
    $stmt = $pdo->prepare("SELECT name, image, category FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $parent = $stmt->fetch();
    
    if (!$parent) { header("Location: products.php"); exit; }

    // 2. Fetch Variant Info & Validate Stock
    if ($variant_id) {
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE id = ?");
        $stmt->execute([$variant_id]);
        $variant = $stmt->fetch();
        
        if ($variant) {
            $db_stock = $variant['stock'];
            $final_price = $variant['price'];
            $cart_key = "v_" . $variant_id; // Unique Key for Session
            
            // --- SMART NAME GENERATION ---
            // If it's an accessory (Standard/Empty options), don't clutter the name
            $specs = [];
            if ($variant['color'] !== 'Standard' && $variant['color'] !== '') $specs[] = $variant['color'];
            if ($variant['storage'] !== '-' && $variant['storage'] !== '') $specs[] = $variant['storage'];
            
            // If specs exist, append them. If not, just use Product Name.
            if (!empty($specs)) {
                $final_name = $parent['name'] . " (" . implode(", ", $specs) . ")";
                $options_str = implode(" | ", $specs);
            } else {
                $final_name = $parent['name'];
                $options_str = "Standard Unit";
            }

        } else {
            header("Location: products.php?error=variant_not_found"); exit;
        }
    } else {
        // If they managed to submit without a variant ID (rare now), bounce them back
        header("Location: product_details.php?id=$product_id&error=select_option"); exit; 
    }

    // 3. CALCULATE TOTAL & CHECK LIMITS
    $current_qty_in_cart = isset($_SESSION['cart'][$cart_key]) ? $_SESSION['cart'][$cart_key]['quantity'] : 0;
    $total_desired = $current_qty_in_cart + $qty_to_add;

    if ($total_desired > $db_stock) {
        $remaining = $db_stock - $current_qty_in_cart;
        $msg = ($remaining > 0) ? "Only $remaining more available." : "Stock limit reached for this item.";
        header("Location: product_details.php?id=$product_id&error=" . urlencode($msg));
        exit;
    }

    // 4. SAVE TO SESSION
    // Note: We use 'product_id' here so place_order.php can read it easily
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['quantity'] += $qty_to_add;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'product_id' => $product_id, // Matched to place_order.php
            'variant_id' => $variant_id, // Matched to place_order.php
            'name'       => $final_name,
            'price'      => $final_price,
            'image'      => $parent['image'], 
            'quantity'   => $qty_to_add,
            'options'    => $options_str // Saved string for email/display
        ];
    }

    header("Location: cart.php");
    exit;
}

// ==========================================
// LOGIC: UPDATE / REMOVE
// ==========================================
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id']; // This is the cart_key (e.g., v_5)
    
    switch ($action) {
        case 'update':
            $new_qty = (int)$_GET['qty'];
            
            // Validate Variant Stock again
            $parts = explode('_', $id);
            $v_id = $parts[1] ?? 0;

            $stmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ?");
            $stmt->execute([$v_id]);
            $var = $stmt->fetch();
            $max_stock = $var ? $var['stock'] : 0;

            if (isset($_SESSION['cart'][$id])) {
                if ($new_qty > $max_stock) {
                    header("Location: cart.php?error=" . urlencode("Limit reached! Only $max_stock items in stock."));
                    exit;
                } elseif ($new_qty > 0) {
                    $_SESSION['cart'][$id]['quantity'] = $new_qty;
                } else {
                    unset($_SESSION['cart'][$id]); // If 0, remove
                }
            }
            break;

        case 'remove':
            if (isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
            break;

        case 'clear':
            unset($_SESSION['cart']);
            break;
    }
    header("Location: cart.php");
    exit;
}

// Default redirect
header("Location: products.php");
exit;
?>
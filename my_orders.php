<?php
session_start();

// 1. CONNECT TO DATABASE
if (file_exists('admin/db.php')) { require_once 'admin/db.php'; }
elseif (file_exists('db.php')) { require_once 'db.php'; }
elseif (file_exists('../db.php')) { require_once '../db.php'; }

// 2. FORCE LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 3. FETCH USER'S EMAIL
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_email = $user['email'] ?? '';

// 4. CALCULATE CART COUNT FOR NAVIGATION
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_count = 0;
if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        if (is_array($item) && isset($item['quantity'])) {
            $cart_count += $item['quantity'];
        } elseif (is_numeric($item)) {
            $cart_count += $item;
        }
    }
}

// 5. FETCH ORDERS WITH THEIR ITEMS
$stmt = $pdo->prepare("
    SELECT 
        orders.*, 
        COUNT(order_items.id) as item_count,
        GROUP_CONCAT(CONCAT(products.name, ' (x', order_items.quantity, ')') SEPARATOR '||') as items_list
    FROM orders 
    LEFT JOIN order_items ON orders.id = order_items.order_id 
    LEFT JOIN products ON order_items.product_id = products.id
    WHERE orders.customer_email = ? 
    GROUP BY orders.id 
    ORDER BY orders.id DESC
");

$stmt->execute([$user_email]); 
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - Jilomaac</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styled.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <script>
        tailwind.config = {
            corePlugins: { preflight: false },
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#645bff", 
                        "background-dark": "#111827", 
                        "surface-dark": "#1F2937",
                    },
                    fontFamily: { display: ["Inter", "sans-serif"] },
                },
            },
        };
    </script>
</head>
<body class="bg-gray-50 dark:bg-background-dark text-gray-900 dark:text-white transition-colors duration-300 min-h-screen">

    <nav id="mainNav">
        <div class="left">
            <div class="tall-text">
                <section class="JIL">JIL</section>
                <div class="highlight"><img src="assets/images/Adobe Express - file.png" alt="Logo" /></div>
                <div><section class="MAAC">MAAC</section></div>
            </div>
        </div>
        
        <div class="rightmost">
            <a href="products.php" class="back-cart-btn">
                <span class="material-icons-outlined arrow-icon">arrow_back</span>
                <span class="btn-text">Back to Shop</span>
            </a>
        </div>
        
        <div class="mobile-dropdown-trigger menu-toggle-btn" id="mobileMenuBtn">
            <svg class="step-icon" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line class="line top" x1="4" y1="6" x2="20" y2="6" />
                <line class="line middle" x1="4" y1="12" x2="20" y2="12" />
                <line class="line bottom" x1="4" y1="18" x2="20" y2="18" />
            </svg>
        </div>
    </nav>

    <div id="mobile-menu-overlay" class="mobile-nav-overlay">
        <div class="mobile-nav-container">
            <header>
                <div class="mobile-nav-header">
                    <div class="left mobile-nav-title">
                        <div class="tall-text">
                            <section class="JIL" style="color: white;">JIL</section>
                            <div class="highlight"><img src="assets/images/Adobe Express - file.png" alt="" /></div>
                            <div><section class="MAAC" style="color: white;">MAAC</section></div>
                        </div>
                    </div>
                    <button id="close-mobile-menu" class="mobile-close-btn">
                        <span class="material-icons-round">close</span>
                    </button>
                </div>
            </header>

            <ul class="mobile-nav-list">
                <li><a href="index.php" class="mobile-nav-item"><span>Home</span></a></li>
                <li>
                    <a href="products.php" class="mobile-nav-item">
                        <span>Phones</span>
                        <span class="material-icons-round mobile-nav-icon">chevron_right</span>
                    </a>
                </li>
                <li>
                    <a href="accesories.php" class="mobile-nav-item">
                        <span>Accessories</span>
                        <span class="material-icons-round mobile-nav-icon">chevron_right</span>
                    </a>
                </li>
            </ul>

            <div class="mobile-nav-footer">
                <a href="cart.php" class="mobile-cart-btn">
                    <span class="material-icons-round">shopping_cart</span>
                    <span>View Cart (<?php echo $cart_count; ?>)</span>
                </a>
                <div class="mobile-login-area">
                    <span style="color: #6b7280; font-size: 1rem;">Logged in as <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="max-w-4xl mx-auto p-4 md:p-6 min-h-[80vh] mt-12">
        
        <h2 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white mb-8 border-b pb-4 border-gray-200 dark:border-gray-700">Order History</h2>

        <?php if(empty($orders)): ?>
            <div class="text-center mt-12 bg-white dark:bg-surface-dark p-10 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-50 dark:bg-gray-800 mb-6">
                    <span class="material-icons-round text-4xl text-gray-300">inventory_2</span>
                </div>
                <h2 class="text-2xl font-black mb-2 text-gray-900 dark:text-white">No orders found</h2>
                <p class="text-gray-500 mb-8">You haven't placed any orders yet. Let's fix that!</p>
                <a href="products.php" class="bg-primary hover:bg-[#4e44e6] text-white px-8 py-3.5 rounded-full font-bold transition-all shadow-lg shadow-primary/30">Start Shopping</a>
            </div>
        <?php else: ?>
            
            <div class="space-y-4 md:space-y-6">
                <?php foreach($orders as $order): 
                    $date_str = $order['created_at'] ?? $order['order_date'] ?? null;
                    $display_date = $date_str ? date('F d, Y • h:i A', strtotime($date_str)) : 'Date N/A';
                    
                    $status = strtolower($order['status']);
                    switch($status) {
                        case 'completed':
                        case 'delivered':
                            $status_color = 'bg-green-100 text-green-700 border-green-200';
                            $icon = 'check_circle';
                            break;
                        case 'shipped':
                            $status_color = 'bg-blue-100 text-blue-700 border-blue-200';
                            $icon = 'local_shipping';
                            break;
                        case 'cancelled':
                            $status_color = 'bg-red-100 text-red-700 border-red-200';
                            $icon = 'cancel';
                            break;
                        default:
                            $status_color = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                            $icon = 'pending';
                    }

                    $items_list = $order['items_list'] ? explode('||', $order['items_list']) : [];
                ?>
                
                <div class="bg-white dark:bg-surface-dark rounded-[2rem] p-6 md:p-8 shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300 relative overflow-hidden">
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 border-b border-gray-50 dark:border-gray-700 pb-4">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1 block">Order ID: #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-1">
                                <span class="material-icons-round text-sm">schedule</span> <?php echo $display_date; ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border <?php echo $status_color; ?> uppercase tracking-wider shadow-sm">
                            <span class="material-icons-round text-sm"><?php echo $icon; ?></span>
                            <?php echo htmlspecialchars($order['status']); ?>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Items Purchased</h4>
                        <div class="space-y-2">
                            <?php foreach($items_list as $item_text): ?>
                                <div class="flex items-center gap-2 text-sm md:text-base font-semibold text-gray-700 dark:text-gray-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                                    <span><?php echo htmlspecialchars($item_text); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row justify-between items-end gap-4 bg-gray-50 dark:bg-gray-800 p-4 rounded-2xl">
                        <div class="w-full md:w-auto text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                            <span class="font-bold text-gray-700 dark:text-gray-200">Deliver to:</span> <br>
                            <?php echo htmlspecialchars($order['customer_name']); ?><br>
                            <?php echo htmlspecialchars($order['customer_address'] ?? 'No address provided'); ?><br>
                            <?php if(!empty($order['shipping_state'])): ?>
                                <span class="font-bold text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($order['shipping_state']); ?> State</span>
                            <?php endif; ?>
                        </div>
                        <div class="w-full md:w-auto text-left md:text-right">
                            <span class="block text-xs text-gray-500 uppercase font-bold mb-1">Total Paid</span>
                            <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">₦<?php echo number_format($order['total_amount']); ?></span>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <script src="assets/js/script.js"></script>

</body>
</html>
<?php
session_start();

// 1. DATABASE CONNECTION
if (file_exists('../db.php')) { require_once '../db.php'; }
elseif (file_exists('db.php')) { require_once 'db.php'; }

// 2. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 3. GET ORDER ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: orders.php");
    exit;
}
$order_id = $_GET['id'];

// 4. HANDLE STATUS UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    header("Location: order_details.php?id=" . $order_id . "&msg=updated");
    exit;
}

// 5. FETCH ORDER DETAILS
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) { die("Order not found."); }

// 6. FETCH ORDER ITEMS (Removed category column to prevent error)
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image as product_image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7. FETCH CUSTOMER HISTORY
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_email = ?");
$stmt->execute([$order['customer_email']]);
$customer_order_count = $stmt->fetchColumn();

// 8. CALCULATIONS
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += ($item['price_at_purchase'] * $item['quantity']);
}
$shipping_cost = $order['total_amount'] - $subtotal;
if ($shipping_cost < 0) $shipping_cost = 0; 

// Initials Helper
$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1));
$customer_initials = strtoupper(substr($order['customer_name'], 0, 2));
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Order #<?php echo $order['id']; ?> - Details</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#695cff",
                    "background-light": "#f6f5f8",
                    "background-dark": "#100f23",
                },
                fontFamily: { "display": ["Manrope", "sans-serif"] },
            },
        },
    }
</script>
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; font-size: 20px; }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-[#111018] dark:text-white transition-colors duration-200">

<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-[#dbdae7] dark:border-[#2a2845] bg-white dark:bg-[#1a1932] px-10 py-3 sticky top-0 z-50">
    <div class="flex items-center gap-8">
        <div class="flex items-center gap-4 text-primary">
            <div class="size-8 bg-primary rounded-lg flex items-center justify-center text-white">
                <span class="material-symbols-outlined">smartphone</span>
            </div>
            <h2 class="text-[#111018] dark:text-white text-lg font-bold">JIL MAAC Admin</h2>
        </div>
        <nav class="hidden md:flex items-center gap-9">
            <a class="text-[#625e8d] dark:text-gray-400 hover:text-primary transition-colors text-sm font-medium" href="dashboard.php">Dashboard</a>
            <a class="text-primary text-sm font-bold border-b-2 border-primary py-1" href="orders.php">Orders</a>
            <a class="text-[#625e8d] dark:text-gray-400 hover:text-primary transition-colors text-sm font-medium" href="admin_products.php">Inventory</a>
        </nav>
    </div>
    <div class="flex flex-1 justify-end gap-6 items-center">
         <div class="bg-primary/10 rounded-full size-10 flex items-center justify-center text-primary font-bold">
            <?php echo $user_initial; ?>
        </div>
    </div>
</header>

<main class="max-w-[1280px] mx-auto px-4 sm:px-10 py-8">
    
    <div class="flex flex-wrap gap-2 items-center mb-4">
        <a class="text-[#625e8d] dark:text-gray-400 text-sm font-medium hover:text-primary transition-colors" href="orders.php">Orders</a>
        <span class="material-symbols-outlined text-[#625e8d] text-sm">chevron_right</span>
        <span class="text-[#111018] dark:text-white text-sm font-semibold">Order #<?php echo $order['id']; ?></span>
    </div>

    <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-3">
                <h1 class="text-[#111018] dark:text-white text-4xl font-black">Order #<?php echo $order['id']; ?></h1>
                
                <?php 
                    $status = strtolower($order['status']);
                    $statusColor = 'bg-gray-100 text-gray-600 border-gray-200';
                    if($status == 'completed') $statusColor = 'bg-green-100 text-green-700 border-green-200';
                    if($status == 'pending') $statusColor = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                    if($status == 'cancelled') $statusColor = 'bg-red-100 text-red-700 border-red-200';
                    if($status == 'shipped') $statusColor = 'bg-blue-100 text-blue-700 border-blue-200';
                ?>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border <?php echo $statusColor; ?>">
                    <?php echo ucfirst($status); ?>
                </span>
            </div>
            <p class="text-[#625e8d] dark:text-gray-400 text-base font-normal">
                Placed on <?php echo date('M d, Y', strtotime($order['created_at'])); ?> at <?php echo date('h:i A', strtotime($order['created_at'])); ?>
            </p>
        </div>

        <div class="flex gap-3">
            <form method="POST" class="flex gap-2">
                <select name="status" class="bg-white dark:bg-[#1a1932] border border-[#dbdae7] dark:border-[#2a2845] rounded-lg text-sm font-bold text-[#111018] dark:text-white px-3 py-2 focus:ring-primary focus:border-primary cursor-pointer">
                    <option value="pending" <?php if($status=='pending') echo 'selected'; ?>>Pending</option>
                    <option value="completed" <?php if($status=='completed') echo 'selected'; ?>>Completed</option>
                    <option value="shipped" <?php if($status=='shipped') echo 'selected'; ?>>Shipped</option>
                    <option value="cancelled" <?php if($status=='cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
                <button type="submit" name="update_status" class="flex items-center gap-2 px-6 py-2 bg-primary text-white rounded-lg text-sm font-bold shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">
                    Update
                </button>
            </form>
            
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-[#1a1932] border border-[#dbdae7] dark:border-[#2a2845] rounded-lg text-sm font-bold text-[#111018] dark:text-white hover:bg-gray-50 transition-colors shadow-sm">
                <span class="material-symbols-outlined">print</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 flex flex-col gap-8">
            
            <div class="bg-white dark:bg-[#1a1932] rounded-xl border border-[#dbdae7] dark:border-[#2a2845] overflow-hidden shadow-sm">
                <div class="p-6 border-b border-[#dbdae7] dark:border-[#2a2845] flex justify-between items-center">
                    <h3 class="text-lg font-bold text-[#111018] dark:text-white">Order Items (<?php echo count($items); ?>)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-[#f8f9fa] dark:bg-[#21203a]">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-[#625e8d] dark:text-gray-400">Product</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-[#625e8d] dark:text-gray-400">Price</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-[#625e8d] dark:text-gray-400 text-center">Qty</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-[#625e8d] dark:text-gray-400 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#dbdae7] dark:divide-[#2a2845]">
                            
                            <?php foreach($items as $item): 
                                $img_src = (!empty($item['product_image']) && file_exists('../assets/images/'.$item['product_image'])) 
                                            ? '../assets/images/'.$item['product_image'] 
                                            : 'https://via.placeholder.com/64?text=No+Img';
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#252445] transition-colors">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="size-16 rounded-lg bg-gray-100 flex-shrink-0 bg-center bg-cover border border-gray-200" style='background-image: url("<?php echo $img_src; ?>");'></div>
                                        <div>
                                            <p class="text-sm font-bold text-[#111018] dark:text-white">
                                                <?php echo htmlspecialchars($item['product_name'] ?? 'Product Deleted'); ?>
                                            </p>
                                            <p class="text-xs text-[#625e8d] dark:text-gray-400 font-mono mt-1">
                                                ID: #<?php echo $item['product_id']; ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-sm font-medium text-[#111018] dark:text-white">₦<?php echo number_format($item['price_at_purchase']); ?></td>
                                <td class="px-6 py-5 text-sm font-medium text-center text-[#111018] dark:text-white"><?php echo $item['quantity']; ?></td>
                                <td class="px-6 py-5 text-sm font-bold text-right text-[#111018] dark:text-white">
                                    ₦<?php echo number_format($item['price_at_purchase'] * $item['quantity']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <div class="bg-white dark:bg-[#1a1932] rounded-xl border border-[#dbdae7] dark:border-[#2a2845] p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-[#111018] dark:text-white mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">credit_card</span>
                        Payment Method
                    </h3>
                    <div class="flex items-center gap-4 p-4 rounded-lg bg-[#f8f9fa] dark:bg-[#21203a] border border-[#dbdae7] dark:border-[#2a2845]">
                        <div class="w-12 h-8 bg-blue-600 rounded flex items-center justify-center font-bold text-[10px] text-white">PAY</div>
                        <div>
                            <p class="text-sm font-bold text-[#111018] dark:text-white">Paystack / Card</p>
                            <p class="text-xs text-[#625e8d] dark:text-gray-400 truncate w-40">Ref: <?php echo htmlspecialchars($order['payment_ref']); ?></p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-green-600 font-bold text-sm">
                        <span class="material-symbols-outlined text-sm">check_circle</span>
                        Payment Successful
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1a1932] rounded-xl border border-[#dbdae7] dark:border-[#2a2845] p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-[#111018] dark:text-white mb-4">Cost Summary</h3>
                    <div class="flex flex-col gap-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-[#625e8d] dark:text-gray-400">Subtotal</span>
                            <span class="font-medium text-[#111018] dark:text-white">₦<?php echo number_format($subtotal); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#625e8d] dark:text-gray-400">Shipping</span>
                            <span class="font-medium text-[#111018] dark:text-white">₦<?php echo number_format($shipping_cost); ?></span>
                        </div>
                        <div class="h-px bg-[#dbdae7] dark:bg-[#2a2845] my-1"></div>
                        <div class="flex justify-between text-lg font-black">
                            <span class="text-[#111018] dark:text-white">Total</span>
                            <span class="text-primary tracking-tight">₦<?php echo number_format($order['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($order['total_amount'] > 150000): ?>
            <div class="rounded-xl bg-gradient-to-r from-primary to-[#8e84ff] p-6 flex items-center justify-between text-white overflow-hidden relative min-h-[140px] shadow-lg shadow-primary/20">
                <div class="relative z-10">
                    <h4 class="text-xl font-black mb-1">Premium Customer Service</h4>
                    <p class="text-white/80 text-sm max-w-[400px]">
                        High Value Order (Over ₦150k). This customer qualifies for the JIL MAAC Elite priority support program.
                    </p>
                    <button class="mt-4 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-xs font-bold transition-colors">
                        View Program Details
                    </button>
                </div>
                <div class="absolute right-[-20px] bottom-[-20px] opacity-20 rotate-12">
                    <span class="material-symbols-outlined !text-[160px]">verified_user</span>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <div class="flex flex-col gap-8">
            
            <div class="bg-white dark:bg-[#1a1932] rounded-xl border border-[#dbdae7] dark:border-[#2a2845] p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-[#111018] dark:text-white">Customer</h3>
                </div>
                <div class="flex items-center gap-4 mb-6">
                    <div class="size-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-lg">
                        <?php echo $customer_initials; ?>
                    </div>
                    <div>
                        <p class="font-bold text-[#111018] dark:text-white"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p class="text-xs text-[#625e8d] dark:text-gray-400"><?php echo $customer_order_count; ?> Orders on record</p>
                    </div>
                </div>
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3 text-sm">
                        <span class="material-symbols-outlined text-[#625e8d]">mail</span>
                        <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>" class="text-[#111018] dark:text-white truncate hover:text-primary">
                            <?php echo htmlspecialchars($order['customer_email']); ?>
                        </a>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="material-symbols-outlined text-[#625e8d]">call</span>
                        <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>" class="text-[#111018] dark:text-white hover:text-primary">
                            <?php echo htmlspecialchars($order['customer_phone']); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#1a1932] rounded-xl border border-[#dbdae7] dark:border-[#2a2845] p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-[#111018] dark:text-white">Shipping Address</h3>
                </div>
                <div class="bg-[#f8f9fa] dark:bg-[#21203a] p-4 rounded-lg border border-[#dbdae7] dark:border-[#2a2845]">
                    <p class="text-sm font-bold text-[#111018] dark:text-white mb-1"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p class="text-sm text-[#625e8d] dark:text-gray-400 leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?>
                    </p>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-blue-600">
                        <span class="material-symbols-outlined">local_shipping</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-[#625e8d] dark:text-gray-400 uppercase tracking-tighter">Delivery Method</p>
                        <p class="text-xs font-bold text-[#111018] dark:text-white">Standard Shipping</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#1a1932] rounded-xl border border-[#dbdae7] dark:border-[#2a2845] p-6 shadow-sm">
                <h3 class="text-lg font-bold text-[#111018] dark:text-white mb-6">Order Timeline</h3>
                <div class="flex flex-col gap-6 relative before:content-[''] before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-gray-100 dark:before:bg-[#2a2845]">
                    
                    <div class="flex gap-4 relative">
                        <div class="size-[24px] rounded-full bg-primary flex items-center justify-center text-white z-10 shadow-lg shadow-primary/30">
                            <span class="material-symbols-outlined !text-[14px]">sync</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-[#111018] dark:text-white leading-tight">Current: <?php echo ucfirst($status); ?></p>
                            <p class="text-[10px] text-[#625e8d] dark:text-gray-400 uppercase font-bold mt-0.5">Updated: Now</p>
                        </div>
                    </div>

                    <div class="flex gap-4 relative">
                        <div class="size-[24px] rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500 z-10">
                            <span class="material-symbols-outlined !text-[14px]">shopping_cart</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-[#111018] dark:text-white leading-tight">Order Placed</p>
                            <p class="text-[10px] text-[#625e8d] dark:text-gray-400 uppercase font-bold mt-0.5"><?php echo date('M d, h:i A', strtotime($order['created_at'])); ?></p>
                            <p class="text-xs text-[#625e8d] dark:text-gray-400 mt-1">Received via Webshop</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

</main>

<footer class="max-w-[1280px] mx-auto px-10 py-10 text-center">
    <p class="text-xs text-[#625e8d] dark:text-gray-500">© <?php echo date('Y'); ?> JIL MAAC Admin. All rights reserved.</p>
</footer>

</body>
</html>
<?php
session_start();
require_once '../db.php'; 

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../login.php"); // Uncomment when live
    // exit;
}

// 2. LOGIC: HANDLE ANNOUNCEMENT UPDATE
$anno_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_announcement'])) {
    $text = $_POST['announcement_text'];
    $active = isset($_POST['announcement_active']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE settings SET announcement_text = ?, is_active = ? WHERE id = 1");
        $stmt->execute([$text, $active]);
        $anno_message = "Announcement updated successfully!";
    } catch (PDOException $e) {
        $anno_message = "Error: " . $e->getMessage();
    }
}

// 3. FETCH DATA
try {
    // A. Fetch Announcement Settings
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    // Default if table is empty
    if(!$announcement) { $announcement = ['announcement_text' => '', 'is_active' => 0]; }

    // --- NEW: FETCH CAROUSEL COUNT ---
    $checkCarousel = $pdo->query("SHOW TABLES LIKE 'carousel_slides'");
    $total_slides = 0;
    if($checkCarousel->rowCount() > 0) {
        $stmt = $pdo->query("SELECT count(*) FROM carousel_slides");
        $total_slides = $stmt->fetchColumn() ?: 0;
    }
    // ---------------------------------

    // B. Stats & Products
    $stmt = $pdo->query("SELECT count(*) FROM products");
    $total_products = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->query("SELECT * FROM products WHERE stock_quantity < 3 LIMIT 5");
    $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $low_stock_count = count($low_stock_items);

    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 3");
    $recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // C. Revenue
    $total_revenue = 0;
    $total_sales_count = 0;
    $recent_orders = [];

    $checkTable = $pdo->query("SHOW TABLES LIKE 'orders'");
    if($checkTable->rowCount() > 0) {
        $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'completed'");
        $total_revenue = $stmt->fetchColumn() ?: 0;

        $stmt = $pdo->query("SELECT count(*) FROM orders");
        $total_sales_count = $stmt->fetchColumn() ?: 0;
        
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $total_products = 0;
}

$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Jilomaac Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#2563EB", 
                        secondary: "#1E40AF",
                        "background-light": "#F3F4F6", 
                        "background-dark": "#111827", 
                        "surface-light": "#FFFFFF",
                        "surface-dark": "#1F2937",
                    },
                    fontFamily: { body: ["Poppins", "sans-serif"] },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
        .sidebar-item-active { background-color: #EFF6FF; color: #2563EB; border-right: 3px solid #2563EB; }
        .dark .sidebar-item-active { background-color: #1E3A8A; color: #60A5FA; border-right: 3px solid #60A5FA; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-gray-800 dark:text-gray-200 transition-colors duration-200">
    
<div class="flex h-screen overflow-hidden">
    
    <aside class="w-64 bg-surface-light dark:bg-surface-dark shadow-xl flex-shrink-0 hidden md:flex flex-col z-20">
        <div class="h-20 flex items-center px-8 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-lg">
                    <span class="material-symbols-outlined text-sm">bolt</span>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">JIL <span class="text-primary">MAAC</span></h1>
            </div>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a class="sidebar-item-active flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-colors" href="dashboard.php">
                <span class="material-symbols-outlined">dashboard</span> Dashboard
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary rounded-lg transition-colors" href="admin_products.php">
                <span class="material-symbols-outlined">inventory_2</span> Inventory
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary rounded-lg transition-colors" href="orders.php">
                <span class="material-symbols-outlined">shopping_bag</span> Orders
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary rounded-lg transition-colors" href="add_product.php">
                <span class="material-symbols-outlined">add_circle</span> Add Product
            </a>

            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary rounded-lg transition-colors" href="admin_carousel.php">
                <span class="material-symbols-outlined">view_carousel</span> Carousel
            </a>

            <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
                <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</h3>
                <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-red-500 rounded-lg transition-colors" href="../logout.php">
                    <span class="material-symbols-outlined">logout</span> Logout
                </a>
            </div>
        </nav>

        <?php if($low_stock_count > 0): ?>
        <div class="p-4 m-4 bg-gradient-to-br from-red-500 to-red-700 rounded-2xl relative overflow-hidden text-white shadow-lg">
            <div class="relative z-10">
                <h4 class="font-bold text-sm mb-1">Stock Alert!</h4>
                <p class="text-xs text-red-100 mb-3"><?php echo $low_stock_count; ?> items running low.</p>
                <a href="admin_products.php" class="text-xs bg-white text-red-600 px-3 py-1.5 rounded-full font-semibold hover:bg-gray-100 transition">Restock</a>
            </div>
            <div class="absolute -bottom-6 -right-6 w-24 h-24 bg-white opacity-20 rounded-full"></div>
        </div>
        <?php endif; ?>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <header class="h-20 bg-surface-light dark:bg-surface-dark shadow-sm flex items-center justify-between px-8 z-10 transition-colors">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-gray-500 hover:text-gray-700 dark:text-gray-300">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white hidden sm:block">Admin Overview</h2>
            </div>
            
            <div class="flex items-center gap-6">
                <button class="p-2 text-gray-500 hover:text-primary dark:text-gray-400 dark:hover:text-white transition-colors" onclick="document.documentElement.classList.toggle('dark')">
                    <span class="material-symbols-outlined dark:hidden">dark_mode</span>
                    <span class="material-symbols-outlined hidden dark:inline">light_mode</span>
                </button>

                <div class="flex items-center gap-3 pl-4 border-l border-gray-200 dark:border-gray-700">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Administrator</p>
                    </div>
                    <?php if(isset($_SESSION['user_picture']) && !empty($_SESSION['user_picture'])): ?>
                        <img src="<?php echo $_SESSION['user_picture']; ?>" class="w-10 h-10 rounded-full object-cover border-2 border-primary">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold border-2 border-blue-400">
                            <?php echo $user_initial; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-background-light dark:bg-background-dark p-8">
            
            <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>! 👋</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Here is your store performance.</p>
                </div>
                <div class="flex gap-3">
                    <a href="admin_carousel.php" class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 shadow-lg shadow-purple-500/30 transition">
                        <span class="material-symbols-outlined text-sm">view_carousel</span> Manage Carousel (<?php echo $total_slides; ?>)
                    </a>

                    <a href="add_product.php" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition">
                        <span class="material-symbols-outlined text-sm">add_circle</span> Add Product
                    </a>
                </div>
            </div>

            <?php if(!empty($anno_message)): ?>
            <div class="mb-6 p-4 rounded-xl bg-green-50 text-green-700 border border-green-200 flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span> <?php echo $anno_message; ?>
            </div>
            <?php endif; ?>

            <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 mb-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">campaign</span>
                    Store Announcement Bar
                </h3>
                
                <form method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Message</label>
                        <input type="text" name="announcement_text" value="<?php echo htmlspecialchars($announcement['announcement_text']); ?>" 
                            class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary focus:ring-primary py-2.5 px-4 text-sm font-medium placeholder:text-gray-400"
                            placeholder="e.g. 50% Off Flash Sale!">
                    </div>
                    
                    <div class="flex items-center gap-2 mb-2 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600">
                        <input type="checkbox" name="announcement_active" id="anno_active" class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary cursor-pointer" 
                            <?php echo $announcement['is_active'] ? 'checked' : ''; ?>>
                        <label for="anno_active" class="text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer">Active</label>
                    </div>

                    <button type="submit" name="update_announcement" class="w-full sm:w-auto px-6 py-2.5 bg-primary text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-500/30 flex justify-center items-center gap-2">
                        <span class="material-symbols-outlined text-lg">save</span> Save
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-primary">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                        <span class="flex items-center text-sm font-medium text-green-500 bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded-full">
                            Verified
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">₦<?php echo number_format($total_revenue); ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                </div>

                <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600">
                            <span class="material-symbols-outlined">shopping_bag</span>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($total_sales_count); ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Orders Received</p>
                </div>

                <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-yellow-600">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $total_products; ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Products</p>
                </div>

                <div class="<?php echo $low_stock_count > 0 ? 'bg-red-50 dark:bg-red-900/20 border-red-200' : 'bg-surface-light dark:bg-surface-dark border-gray-100'; ?> p-6 rounded-2xl shadow-sm border">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-white rounded-lg text-red-600 shadow-sm">
                            <span class="material-symbols-outlined">warning</span>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $low_stock_count; ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Low Stock Items</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                
                <div class="lg:col-span-2 bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Newest Inventory</h3>
                        <a href="admin_products.php" class="text-sm text-primary font-medium hover:underline">View All</a>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <?php if(empty($recent_products)): ?>
                            <div class="col-span-3 text-center py-4 text-gray-500">No products added yet.</div>
                        <?php else: ?>
                            <?php foreach($recent_products as $product): ?>
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 flex flex-col items-center text-center hover:shadow-md transition">
                                <div class="w-20 h-24 mb-3 bg-white dark:bg-gray-700 rounded-lg flex items-center justify-center shadow-sm overflow-hidden p-2">
                                    <?php if($product['image'] && file_exists('../assets/images/' . $product['image'])): ?>
                                        <img src="../assets/images/<?php echo htmlspecialchars($product['image']); ?>" class="w-full h-full object-contain">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-4xl text-gray-400">smartphone</span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-1"><?php echo htmlspecialchars($product['name']); ?></h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">ID: #<?php echo $product['id']; ?></p>
                                
                                <div class="flex justify-between w-full items-center mt-auto pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span class="text-xs font-bold text-primary">₦<?php echo number_format($product['price']); ?></span>
                                    <span class="text-xs font-medium <?php echo $product['stock_quantity'] < 3 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> px-2 py-0.5 rounded-full">
                                        <?php echo $product['stock_quantity']; ?> left
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lg:col-span-1 bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Recent Activity</h3>
                    <div class="space-y-6 overflow-y-auto pr-2" style="max-height: 320px;">
                        
                        <?php foreach($low_stock_items as $item): ?>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 flex-shrink-0">
                                <span class="material-symbols-outlined text-sm">warning</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Low Stock: <?php echo htmlspecialchars($item['name']); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Only <?php echo $item['stock_quantity']; ?> units remaining.</p>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php foreach(array_slice($recent_orders, 0, 3) as $order): ?>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-primary flex-shrink-0">
                                <span class="material-symbols-outlined text-sm">shopping_cart</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">New Order #<?php echo $order['id']; ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Total: ₦<?php echo number_format($order['total_amount']); ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo date('H:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 flex-shrink-0">
                                <span class="material-symbols-outlined text-sm">login</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">System Access</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Logged in as <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Now</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="mt-8 bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Phone Sales</h3>
                    <div class="flex gap-2">
                        <a href="orders.php" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-primary font-medium text-sm">View All Orders</a>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700 text-xs uppercase tracking-wider">
                                <th class="py-4 px-4 font-medium">Order ID</th>
                                <th class="py-4 px-4 font-medium">Date Placed</th>
                                <th class="py-4 px-4 font-medium">Total Price</th>
                                <th class="py-4 px-4 font-medium">Status</th>
                                <th class="py-4 px-4 font-medium text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 dark:text-gray-300 text-sm">
                            <?php if(empty($recent_orders)): ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500">No recent orders found in database.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($recent_orders as $order): ?>
                                <tr class="border-b border-gray-50 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <td class="py-4 px-4 flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-gray-500 text-sm">receipt_long</span>
                                        </div>
                                        <span class="font-medium text-gray-900 dark:text-white">#<?php echo $order['id']; ?></span>
                                    </td>
                                    <td class="py-4 px-4 text-gray-500 dark:text-gray-400"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td class="py-4 px-4 font-semibold">₦<?php echo number_format($order['total_amount'] ?? 0); ?></td>
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php 
                                            $s = strtolower($order['status']);
                                            if($s == 'completed' || $s == 'delivered') echo 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                                            elseif($s == 'pending') echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
                                            elseif($s == 'cancelled') echo 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
                                            else echo 'bg-gray-100 text-gray-800';
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <a class="text-primary hover:text-blue-700 font-medium" href="order_details.php?id=<?php echo $order['id']; ?>">Details</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>
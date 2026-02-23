<?php
session_start();

// 1. CONNECTION
if (file_exists('../db.php')) { require_once '../db.php'; }
elseif (file_exists('db.php')) { require_once 'db.php'; }

// 2. SECURITY
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 3. FETCH ORDERS (Fixed 'order_date' -> 'created_at')
// We also join with order_items to count how many items are in each order
try {
    $sql = "SELECT orders.*, COUNT(order_items.id) as item_count 
            FROM orders 
            LEFT JOIN order_items ON orders.id = order_items.order_id 
            GROUP BY orders.id 
            ORDER BY orders.created_at DESC"; // <--- FIXED THIS LINE
            
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Helper for Initials
$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Order Management - Jilomaac</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: { primary: "#2563EB", "background-light": "#F8FAFC" },
                fontFamily: { body: ["Poppins", "sans-serif"] },
            },
        },
    };
</script>
<style type="text/tailwindcss">
    body { font-family: 'Poppins', sans-serif; }
    .sidebar-item-active { background-color: #EFF6FF; color: #2563EB; border-right: 4px solid #2563EB; }
</style>
</head>
<body class="bg-background-light text-slate-800">

<div class="flex h-screen overflow-hidden">
    
    <aside class="w-64 bg-white shadow-sm flex-shrink-0 hidden md:flex flex-col z-20 border-r border-slate-100">
        <div class="h-20 flex items-center px-8">
            <h1 class="text-xl font-bold tracking-tight text-slate-900">JIL <span class="text-primary">MAAC</span></h1>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg transition-colors" href="dashboard.php">
                <span class="material-symbols-outlined text-[20px]">dashboard</span> Dashboard
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg transition-colors" href="admin_products.php">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span> Inventory
            </a>
            <a class="sidebar-item-active flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-colors" href="orders.php">
                <span class="material-symbols-outlined text-[20px]">shopping_bag</span> Customer Orders
            </a>
            <div class="pt-4 mt-4 border-t border-slate-50">
                <h3 class="px-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Admin</h3>
                <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 hover:text-red-500 rounded-lg transition-colors" href="../logout.php">
                    <span class="material-symbols-outlined text-[20px]">logout</span> Logout
                </a>
            </div>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="h-20 bg-white border-b border-slate-100 flex items-center justify-between px-8 z-10">
            <h2 class="text-xl font-bold text-slate-800">Customer Orders <span class="ml-2 text-sm font-normal text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full"><?php echo count($orders); ?> Orders</span></h2>
            <div class="flex items-center gap-3">
                <a href="../index.php" target="_blank" class="text-sm font-medium text-slate-500 hover:text-primary">View Site ↗</a>
                <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold border-2 border-slate-100"><?php echo $user_initial; ?></div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-background-light p-8">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-4 px-6">Order ID</th>
                            <th class="py-4 px-4">Customer</th>
                            <th class="py-4 px-4">Date</th>
                            <th class="py-4 px-4">Total</th>
                            <th class="py-4 px-4">Status</th>
                            <th class="py-4 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if(empty($orders)): ?>
                            <tr><td colspan="6" class="py-8 text-center text-slate-500">No orders found.</td></tr>
                        <?php else: ?>
                            <?php foreach($orders as $order): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-5 px-6 font-medium">#<?php echo $order['id']; ?></td>
                                <td class="py-5 px-4">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                        <span class="text-xs text-slate-500"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                                    </div>
                                </td>
                                <td class="py-5 px-4 text-sm text-slate-600">
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                </td>
                                <td class="py-5 px-4 font-bold text-primary">
                                    ₦<?php echo number_format($order['total_amount']); ?>
                                </td>
                                <td class="py-5 px-4">
                                    <?php 
                                        $status = strtolower($order['status']);
                                        $colorClass = 'bg-gray-100 text-gray-600';
                                        if($status == 'completed') $colorClass = 'bg-green-100 text-green-700';
                                        if($status == 'pending') $colorClass = 'bg-yellow-100 text-yellow-700';
                                        if($status == 'cancelled') $colorClass = 'bg-red-100 text-red-700';
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?php echo $colorClass; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td class="py-5 px-6 text-right">
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-sm font-medium text-primary hover:underline">View Details</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</div>

</body>
</html>
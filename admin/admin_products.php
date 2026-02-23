<?php
session_start();

// 1. SMART DB CONNECTION
if (file_exists('db.php')) { require_once 'db.php'; }
elseif (file_exists('../db.php')) { require_once '../db.php'; }
elseif (file_exists('config/db.php')) { require_once 'config/db.php'; }
elseif (file_exists('../config/db.php')) { require_once '../config/db.php'; }

// 2. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    // header("Location: ../login.php");
    // exit;
}

// 3. HANDLE BULK DELETE
if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
    $ids_to_delete = $_POST['selected_ids'];
    $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
    
    $sql = "DELETE FROM products WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    if($stmt->execute($ids_to_delete)) {
        $success_msg = "Successfully deleted " . count($ids_to_delete) . " products.";
    } else {
        $error_msg = "Error deleting products.";
    }
}

// 4. HANDLE SINGLE DELETE
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if($stmt->execute([$id])) {
        header("Location: admin_products.php?msg=deleted");
        exit;
    }
}

// 5. FETCH ALL PRODUCTS
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for User Initials
$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>JIL MAAC Inventory Management</title>
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
                    "background-light": "#F8FAFC", 
                    "surface-light": "#FFFFFF",
                    "accent-blue": "#3B82F6",
                },
                fontFamily: {
                    display: ["Poppins", "sans-serif"],
                    body: ["Poppins", "sans-serif"],
                },
            },
        },
    };
</script>
<style type="text/tailwindcss">
    body { font-family: 'Poppins', sans-serif; }
    .sidebar-item-active {
        background-color: #EFF6FF;
        color: #2563EB;
        border-right: 4px solid #2563EB;
    }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 10px; }
    .inventory-table tr:hover { background-color: #F8FAFC; }
    #floatingActionBar { display: none; }
</style>
</head>
<body class="bg-background-light text-slate-800 transition-colors duration-200">

<div class="flex h-screen overflow-hidden">
    
    <aside class="w-64 bg-white shadow-sm flex-shrink-0 hidden md:flex flex-col z-20 border-r border-slate-100">
        <div class="h-20 flex items-center px-8">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center text-white font-bold shadow-lg shadow-blue-500/20">
                    <span class="material-symbols-outlined text-lg">bolt</span>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">JIL <span class="text-primary">MAAC</span></h1>
            </div>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg transition-colors" href="dashboard.php">
                <span class="material-symbols-outlined text-[20px]">dashboard</span> Dashboard
            </a>
            <a class="sidebar-item-active flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-colors" href="admin_products.php">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span> Inventory
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg transition-colors" href="orders.php">
                <span class="material-symbols-outlined text-[20px]">shopping_bag</span> Customer Orders
            </a>
            <div class="pt-4 mt-4 border-t border-slate-50">
                <h3 class="px-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Admin</h3>
                <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 hover:text-red-500 rounded-lg transition-colors" href="../logout.php">
                    <span class="material-symbols-outlined text-[20px]">logout</span> Logout
                </a>
            </div>
        </nav>
        <div class="p-6 mt-auto">
            <div class="relative w-full h-32 flex items-center justify-center overflow-hidden rounded-2xl bg-slate-50">
                <p class="relative z-10 text-[10px] text-slate-400 text-center font-medium px-4">Jiloomac Admin Panel</p>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="h-20 bg-white border-b border-slate-100 flex items-center justify-between px-8 z-10">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-bold text-slate-800">Product Inventory 
                    <span class="ml-2 text-sm font-normal text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full"><?php echo count($products); ?> Items</span>
                </h2>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="../index.php" target="_blank" class="text-sm font-medium text-slate-500 hover:text-primary mr-4">View Live Site ↗</a>
                
                <a href="add_product.php" class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-blue-700 shadow-lg shadow-blue-500/25 transition-all">
                    <span class="material-symbols-outlined text-lg">add</span> Add Product
                </a>
                <div class="h-8 w-px bg-slate-100 mx-2"></div>
                
                <?php if(isset($_SESSION['user_picture']) && !empty($_SESSION['user_picture'])): ?>
                    <img src="<?php echo $_SESSION['user_picture']; ?>" class="w-10 h-10 rounded-full border-2 border-slate-100 object-cover">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold border-2 border-slate-100">
                        <?php echo $user_initial; ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-background-light p-8">
            
            <?php if(isset($success_msg)): ?>
            <div class="mb-4 p-4 rounded-lg bg-green-50 text-green-700 border border-green-200 flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span> <?php echo $success_msg; ?>
            </div>
            <?php endif; ?>

            <?php if(isset($error_msg)): ?>
            <div class="mb-4 p-4 rounded-lg bg-red-50 text-red-700 border border-red-200 flex items-center gap-2">
                <span class="material-symbols-outlined">error</span> <?php echo $error_msg; ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="mainForm">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
                    <table class="w-full text-left border-collapse inventory-table">
                        <thead>
                            <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider font-bold">
                                <th class="py-4 px-6 w-12">
                                    <input id="selectAll" class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4" type="checkbox"/>
                                </th>
                                <th class="py-4 px-4 w-20">Image</th>
                                <th class="py-4 px-4">Product Details</th>
                                <th class="py-4 px-4">Category</th>
                                <th class="py-4 px-4">Stock</th>
                                <th class="py-4 px-4">Price</th>
                                <th class="py-4 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            
                            <?php if(empty($products)): ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-500">No products found. Add one to get started!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($products as $p): 
                                    $stock = $p['stock_quantity'];
                                    $bar_width = ($stock > 50) ? '100%' : ($stock * 2) . '%';
                                    $bar_color = 'bg-green-500';
                                    if($stock == 0) $bar_color = 'bg-red-500';
                                    elseif($stock < 5) $bar_color = 'bg-yellow-500';
                                    $category = $p['category'] ?? 'General';
                                ?>
                                <tr class="transition-all">
                                    <td class="py-5 px-6">
                                        <input name="selected_ids[]" value="<?php echo $p['id']; ?>" class="item-checkbox rounded border-slate-300 text-primary focus:ring-primary h-4 w-4" type="checkbox"/>
                                    </td>
                                    <td class="py-5 px-4">
                                        <div class="w-14 h-14 bg-slate-100 rounded-xl overflow-hidden flex items-center justify-center border border-slate-100">
                                            <?php if($p['image'] && file_exists('../assets/images/' . $p['image'])): ?>
                                                <img src="../assets/images/<?php echo htmlspecialchars($p['image']); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-slate-400">image</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-5 px-4">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($p['name']); ?></span>
                                            <span class="text-xs text-slate-500 mt-0.5 line-clamp-1"><?php echo htmlspecialchars($p['description']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-5 px-4">
                                        <span class="px-2.5 py-1 bg-blue-50 text-blue-600 rounded-full text-[11px] font-bold">
                                            <?php echo htmlspecialchars($category); ?>
                                        </span>
                                    </td>
                                    <td class="py-5 px-4">
                                        <div class="flex flex-col gap-1.5">
                                            <span class="text-sm font-medium text-slate-700"><?php echo $stock; ?> Units</span>
                                            <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="<?php echo $bar_color; ?> h-full" style="width: <?php echo $bar_width; ?>"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-5 px-4">
                                        <span class="text-primary font-bold">₦<?php echo number_format($p['price']); ?></span>
                                    </td>
                                    <td class="py-5 px-6">
                                        <div class="flex items-center justify-end gap-2">
                                            
                                            <a href="manage_variants.php?id=<?php echo $p['id']; ?>" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Manage Variants">
                                                <span class="material-symbols-outlined text-lg">tune</span>
                                            </a>

                                            <a href="admin_edit_product.php?id=<?php echo $p['id']; ?>" class="p-2 text-slate-400 hover:text-primary hover:bg-blue-50 rounded-lg transition" title="Edit">
                                                <span class="material-symbols-outlined text-lg">edit</span>
                                            </a>
                                            <a href="admin_products.php?delete_id=<?php echo $p['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition" title="Delete">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="floatingActionBar" class="fixed bottom-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-8 z-50 animate-in fade-in slide-in-from-bottom-4">
                    <div class="flex items-center gap-3">
                        <span id="selectedCount" class="w-6 h-6 rounded-full bg-primary flex items-center justify-center text-[10px] font-bold">0</span>
                        <span class="text-sm font-medium">Items Selected</span>
                    </div>
                    <div class="h-6 w-px bg-slate-700"></div>
                    <div class="flex items-center gap-4">
                        <button type="submit" name="delete_selected" onclick="return confirm('Delete all selected items?');" class="flex items-center gap-2 text-sm font-medium text-red-400 hover:text-red-300 transition">
                            <span class="material-symbols-outlined text-lg">delete</span>
                            Delete Selected
                        </button>
                    </div>
                </div>
            </form>

        </main>
    </div>
</div>

<script>
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const floatingBar = document.getElementById('floatingActionBar');
    const countBadge = document.getElementById('selectedCount');

    // Handle "Select All" click
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateFloatingBar();
    });

    // Handle individual checkbox clicks
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateFloatingBar);
    });

    function updateFloatingBar() {
        // Count checked boxes
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        
        // Update badge number
        countBadge.innerText = checkedCount;

        // Show/Hide bar
        if (checkedCount > 0) {
            floatingBar.style.display = 'flex';
        } else {
            floatingBar.style.display = 'none';
        }
    }
</script>

</body>
</html>
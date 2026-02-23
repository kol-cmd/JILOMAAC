<?php
session_start();

// 1. SMART DB CONNECTION
if (file_exists('db.php')) { require_once 'db.php'; }
elseif (file_exists('../db.php')) { require_once '../db.php'; }
elseif (file_exists('config/db.php')) { require_once 'config/db.php'; }
elseif (file_exists('../config/db.php')) { require_once '../config/db.php'; }

// 2. SECURITY CHECK
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); 
    die("Access Denied: You must be an admin to view this page.");
}

$main_brands = ['Infinix', 'Tecno', 'Itel', 'Oraimo'];
$message = "";
$msg_type = "";

// 3. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect Data Safely
    $name     = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $brand    = $_POST['brand'] ?? ''; 
    $price    = $_POST['price'] ?? 0;
    $cost     = $_POST['cost_price'] ?? 0;
    $stock    = $_POST['stock'] ?? 0;
    $desc     = $_POST['description'] ?? '';
    $colors   = $_POST['colors'] ?? '';
    $storage  = $_POST['storage'] ?? '';
    $ram      = $_POST['ram'] ?? '';
    
    // Status & SKU Logic (Now safely inside the POST block)
    $status = isset($_POST['online_status']) ? 'active' : 'draft';
    
    // Safety check for category prefix
    $cat_prefix = (!empty($category)) ? strtoupper(substr($category, 0, 2)) : "PR";
    $sku = "JIL-" . $cat_prefix . "-" . rand(1000, 9999);

    // Image Upload Logic
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        
        // Setup Root Path
        if (file_exists(__DIR__ . '/index.php')) { $root_path = __DIR__; } 
        elseif (file_exists(dirname(__DIR__) . '/index.php')) { $root_path = dirname(__DIR__); } 
        else { $root_path = dirname(__DIR__); }
        
        $upload_dir = $root_path . '/assets/images/'; 
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['product_image']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            $new_filename = time() . "_" . uniqid() . "." . $file_ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destination)) {
                
                $sql = "INSERT INTO products (name, category, brand, price, cost_price, stock_quantity, description, image, sku, status, colors, storage, ram) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$name, $category, $brand, $price, $cost, $stock, $desc, $new_filename, $sku, $status, $colors, $storage, $ram])) {
                    $message = "Product Published Successfully! (SKU: $sku)";
                    $msg_type = "success";
                } else {
                    $message = "Database Error: Could not save product.";
                    $msg_type = "error";
                }
            } else {
                $message = "Error moving file.";
                $msg_type = "error";
            }
        } else {
            $message = "Invalid image type.";
            $msg_type = "error";
        }
    } else {
        $message = "Please select a product image.";
        $msg_type = "error";
    }
} // <--- THIS is where the POST block should actually end!

$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Add Product - Jilomaac</title>
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
                    fontFamily: {
                        body: ["Poppins", "sans-serif"],
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
        
        .sidebar-item-active {
            background-color: #EFF6FF;
            color: #2563EB;
            border-right: 3px solid #2563EB;
        }
        .dark .sidebar-item-active {
            background-color: #1E3A8A;
            color: #60A5FA;
            border-right: 3px solid #60A5FA;
        }

        .cat-radio:checked + label {
            background-color: #2563EB;
            color: white;
            border-color: #2563EB;
        }
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
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary rounded-lg transition-colors" href="dashboard.php">
                <span class="material-symbols-outlined">dashboard</span> Dashboard
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary rounded-lg transition-colors" href="admin_products.php">
                <span class="material-symbols-outlined">inventory_2</span> Inventory
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary rounded-lg transition-colors" href="orders.php">
                <span class="material-symbols-outlined">shopping_bag</span> Orders
            </a>
            <a class="sidebar-item-active flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-colors" href="add_product.php">
                <span class="material-symbols-outlined">add_circle</span> Add Product
            </a>

            <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
                <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</h3>
                <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-red-500 rounded-lg transition-colors" href="../logout.php">
                    <span class="material-symbols-outlined">logout</span> Logout
                </a>
            </div>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <header class="h-20 bg-surface-light dark:bg-surface-dark shadow-sm flex items-center justify-between px-8 z-10 transition-colors">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-gray-500 hover:text-gray-700 dark:text-gray-300">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white hidden sm:block">Add New Product</h2>
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
            
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center text-primary text-4xl">
                    <span class="material-symbols-outlined text-4xl">edit_document</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Listing</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Add your latest phones and accessories to the marketplace.</p>
                </div>
            </div>

            <?php if($message): ?>
            <div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?php echo $msg_type == 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                <span class="material-symbols-outlined"><?php echo $msg_type == 'success' ? 'check_circle' : 'error'; ?></span>
                <p class="font-bold text-sm"><?php echo $message; ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-6">Product Information</h2>
                        
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Product Name</label>
                                <input type="text" name="name" placeholder="e.g. iPhone 15 Pro Max" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary focus:ring-primary py-3 px-4 text-sm font-medium">
                            </div>

                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Category</label>
                                    <div class="grid grid-cols-2 gap-4 p-1 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-600">
                                        <div>
                                            <input type="radio" name="category" value="Phones" id="cat_phones" class="cat-radio hidden" checked>
                                            <label for="cat_phones" class="block w-full text-center rounded-lg py-2.5 text-sm font-bold cursor-pointer transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                                Phones
                                            </label>
                                        </div>
                                        <div>
                                            <input type="radio" name="category" value="Accessories" id="cat_access" class="cat-radio hidden">
                                            <label for="cat_access" class="block w-full text-center rounded-lg py-2.5 text-sm font-bold cursor-pointer transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                                Access..
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Brand</label>
                                    <select name="brand" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary focus:ring-primary py-3 px-4 text-sm font-medium appearance-none">
                                        <option value="">Select Brand...</option>
                                        <?php foreach($main_brands as $b): ?>
                                            <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
                                        <?php endforeach; ?>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                <textarea name="description" rows="5" placeholder="Describe features, condition..."
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary focus:ring-primary py-3 px-4 text-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-6">Inventory & Pricing</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Selling Price (₦)</label>
                                <input type="number" name="price" placeholder="0.00" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary focus:ring-primary py-3 px-4 text-sm font-medium">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Cost Price (₦)</label>
                                <input type="number" name="cost_price" placeholder="0.00"
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary focus:ring-primary py-3 px-4 text-sm font-medium">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Stock Quantity</label>
                                <input type="number" name="stock" placeholder="0" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary focus:ring-primary py-3 px-4 text-sm font-medium">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">SKU (Auto)</label>
                                <input type="text" placeholder="JIL-XX-####" disabled
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-400 py-3 px-4 text-sm font-medium cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    

                </div>

                <div class="space-y-6">
                    
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-6">Product Media</h2>
                        
                        <div class="border-2 border-dashed border-primary/20 rounded-2xl p-8 text-center hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors relative group">
                            <input type="file" name="product_image" accept="image/*" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            
                            <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 text-primary rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-2xl">cloud_upload</span>
                            </div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">Upload Product Image</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Drag and drop or click to browse</p>
                            <p class="text-[10px] text-gray-400 mt-2 uppercase">Max Size 5MB</p>
                        </div>
                    </div>

                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-6">Publish Options</h2>
                        
                        <div class="flex items-center justify-between mb-6 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <span class="material-symbols-outlined text-gray-400">public</span> Online Status
                            </span>
                            
                            <label for="online_status" class="flex items-center cursor-pointer relative">
                                <input type="checkbox" name="online_status" id="online_status" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>

                        <button type="submit" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-xl shadow-lg shadow-blue-500/30 text-sm font-bold text-white bg-primary hover:bg-secondary focus:outline-none transition-all hover:-translate-y-0.5">
                            Publish Product
                        </button>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30 flex gap-4">
                        <span class="material-symbols-outlined text-primary mt-0.5">info</span>
                        <div>
                            <p class="text-xs font-bold text-blue-900 dark:text-blue-100 mb-1">Pro Tip:</p>
                            <p class="text-xs text-blue-700 dark:text-blue-200 leading-relaxed font-medium">Use high quality images with white backgrounds.</p>
                        </div>
                    </div>

                </div>
            </form>

        </main>
    </div>
</div>

</body>
</html>
<?php
session_start();

// 1. CONNECT TO DATABASE
if (file_exists('db.php')) { require_once 'db.php'; }
elseif (file_exists('../db.php')) { require_once '../db.php'; }

// 2. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../login.php"); 
    // exit;
}

$id = $_GET['id'] ?? null;
$message = "";
$msg_type = "";

// 3. FETCH EXISTING PRODUCT
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Product not found!");
    }
} else {
    die("No Product ID specified.");
}

// 4. HANDLE UPDATE SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $cost = $_POST['cost_price'];
    $stock = $_POST['stock'];
    $desc = $_POST['description'];
    $status = isset($_POST['online_status']) ? 'active' : 'draft';

    // DEFAULT: Keep old image
    $image_to_save = $product['image']; 
    
    // IMAGE UPLOAD LOGIC (Your Robust Fix)
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['product_image']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            // Define Absolute Path (The Fix)
            $project_root = dirname(__DIR__); 
            $upload_dir = $project_root . '/assets/images/'; 

            // Create folder if missing
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

            $new_filename = time() . "_" . uniqid() . "." . $file_ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destination)) {
                $image_to_save = $new_filename;
            } else {
                $message = "Error: Could not move uploaded file.";
                $msg_type = "error";
            }
        } else {
            $message = "Invalid file type. JPG, PNG, WEBP only.";
            $msg_type = "error";
        }
    }

    // UPDATE DATABASE (Only if no error yet)
    if (empty($message)) {
        $sql = "UPDATE products SET 
                name=?, category=?, price=?, cost_price=?, stock_quantity=?, description=?, image=?, status=? 
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$name, $category, $price, $cost, $stock, $desc, $image_to_save, $status, $id])) {
            $message = "Product updated successfully!";
            $msg_type = "success";
            
            // Refresh the data on the page
            $product['name'] = $name; 
            $product['category'] = $category;
            $product['price'] = $price; 
            $product['cost_price'] = $cost;
            $product['stock_quantity'] = $stock;
            $product['description'] = $desc;
            $product['image'] = $image_to_save;
            $product['status'] = $status;
        } else {
            $message = "Database Error: Could not update.";
            $msg_type = "error";
        }
    }
}

$user_initial = strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Edit Product - Jiloomac</title>
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
        .cat-radio:checked + label { background-color: #2563EB; color: white; border-color: #2563EB; }
        .toggle-checkbox:checked { right: 0; border-color: #2563EB; }
        .toggle-checkbox:checked + .toggle-label { background-color: #2563EB; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-gray-800 dark:text-gray-200">
    
<div class="flex h-screen overflow-hidden">
    
    <aside class="w-64 bg-surface-light dark:bg-surface-dark shadow-xl flex-shrink-0 hidden md:flex flex-col z-20">
        <div class="h-20 flex items-center px-8 border-b border-gray-100 dark:border-gray-700">
            <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">JIL <span class="text-primary">MAAC</span></h1>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-primary rounded-lg" href="dashboard.php">
                <span class="material-symbols-outlined">dashboard</span> Dashboard
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-primary rounded-lg" href="admin_products.php">
                <span class="material-symbols-outlined">inventory_2</span> Inventory
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-primary rounded-lg" href="add_product.php">
                <span class="material-symbols-outlined">add_circle</span> Add Product
            </a>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <header class="h-20 bg-surface-light dark:bg-surface-dark shadow-sm flex items-center justify-between px-8 z-10">
            <div class="flex items-center gap-4">
                <a href="admin_products.php" class="flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-primary">
                    <span class="material-symbols-outlined">arrow_back</span> Back to Inventory
                </a>
            </div>
            <div class="flex items-center gap-3">
                 <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold">
                    <?php echo $user_initial; ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-background-light dark:bg-background-dark p-8">
            
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center text-primary shadow-sm">
                    <span class="material-symbols-outlined text-4xl">edit</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Product</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Updating: <?php echo htmlspecialchars($product['name']); ?></p>
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
                                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary py-3 px-4 text-sm font-medium">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Category</label>
                                <div class="grid grid-cols-2 gap-4 p-1 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-600">
                                    <div>
                                        <input type="radio" name="category" value="Phones" id="cat_phones" class="cat-radio hidden" <?php echo $product['category'] == 'Phones' ? 'checked' : ''; ?>>
                                        <label for="cat_phones" class="block w-full text-center rounded-lg py-2.5 text-sm font-bold cursor-pointer transition-all text-gray-500 hover:text-gray-700">Phones</label>
                                    </div>
                                    <div>
                                        <input type="radio" name="category" value="Accessories" id="cat_access" class="cat-radio hidden" <?php echo $product['category'] == 'Accessories' ? 'checked' : ''; ?>>
                                        <label for="cat_access" class="block w-full text-center rounded-lg py-2.5 text-sm font-bold cursor-pointer transition-all text-gray-500 hover:text-gray-700">Accessories</label>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                <textarea name="description" rows="5" class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary py-3 px-4 text-sm"><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-6">Inventory & Pricing</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Selling Price (₦)</label>
                                <input type="number" name="price" value="<?php echo $product['price']; ?>" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary py-3 px-4 text-sm font-medium">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Cost Price (₦)</label>
                                <input type="number" name="cost_price" value="<?php echo $product['cost_price']; ?>"
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary py-3 px-4 text-sm font-medium">
                            </div>

                            <div class="col-span-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Stock Quantity</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="stock" value="<?php echo $product['stock_quantity']; ?>" required
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary py-3 px-4 text-sm font-medium">
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Change this number to update stock levels.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-6">Current Image</h2>
                        <div class="rounded-lg overflow-hidden border border-gray-200 mb-4 bg-white flex justify-center p-2">
                            <img src="../assets/images/<?php echo $product['image']; ?>" class="h-48 object-contain">
                        </div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Change Image (Optional)</label>
                        <input type="file" name="product_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                         <div class="flex items-center justify-between mb-6 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Online Status</span>
                            <label for="online_status" class="flex items-center cursor-pointer relative">
                                <input type="checkbox" name="online_status" id="online_status" class="sr-only peer" <?php echo $product['status'] == 'active' ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>
                        <button type="submit" class="w-full py-3 px-4 rounded-xl shadow-lg shadow-blue-500/30 text-sm font-bold text-white bg-primary hover:bg-secondary transition-all">
                            Save Changes
                        </button>
                    </div>
                </div>

            </form>
        </main>
    </div>
</div>
</body>
</html>
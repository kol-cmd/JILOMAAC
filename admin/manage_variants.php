<?php
session_start();
require_once '../db.php'; 

// 1. Get Parent Product & Category
if (!isset($_GET['id'])) { header("Location: admin_products.php"); exit; }
$product_id = $_GET['id'];

// Fetch Parent Info (Added 'category' to selection)
$stmt = $pdo->prepare("SELECT name, category, price, stock_quantity FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

$is_accessory = ($product['category'] === 'Accessories');

// 2. CALCULATE STOCK DISTRIBUTION
$stock_check = $pdo->prepare("SELECT SUM(stock) as total_assigned FROM product_variants WHERE product_id = ?");
$stock_check->execute([$product_id]);
$result = $stock_check->fetch();
$total_assigned = $result['total_assigned'] ?? 0;

$master_stock = $product['stock_quantity'];
$remaining_stock = $master_stock - $total_assigned;

$msg = "";
$msg_type = "";

// 3. HANDLE ADD VARIANT
if (isset($_POST['add_variant'])) {
    
    // Auto-fill for accessories, otherwise take POST data
    $color   = $is_accessory ? 'Standard' : $_POST['color'];
    $storage = $is_accessory ? '-' : $_POST['storage'];
    $ram     = $is_accessory ? '-' : $_POST['ram'];
    
    $price = $_POST['price'];
    $new_stock = (int)$_POST['stock'];

    // Validation
    if ($new_stock > $remaining_stock) {
        $msg = "❌ Error: You cannot add $new_stock units. Only $remaining_stock units remain in Master Inventory.";
        $msg_type = "error";
    } else {
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM product_variants WHERE product_id=? AND color=? AND storage=? AND ram=?");
        $check->execute([$product_id, $color, $storage, $ram]);
        
        if ($check->rowCount() == 0) {
            $sql = "INSERT INTO product_variants (product_id, color, storage, ram, price, stock) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$product_id, $color, $storage, $ram, $price, $new_stock])) {
                $msg = "✅ Stock Added Successfully.";
                $msg_type = "success";
                
                // Update display variables immediately
                $remaining_stock -= $new_stock;
                $total_assigned += $new_stock;
            }
        } else {
            $msg = "⚠️ This variant already exists.";
            $msg_type = "warning";
        }
    }
}

// 4. HANDLE DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM product_variants WHERE id=?")->execute([$_GET['delete']]);
    header("Location: manage_variants.php?id=$product_id");
    exit;
}

// 5. Fetch List
$variants = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC");
$variants->execute([$product_id]);
$all_variants = $variants->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Variants - <?php echo htmlspecialchars($product['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
</head>
<body class="bg-gray-50 font-[Poppins] p-10">

<div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow-lg">
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-800">Manage Variants</h1>
                <?php if($is_accessory): ?>
                    <span class="bg-purple-100 text-purple-700 text-xs font-bold px-2 py-1 rounded border border-purple-200">ACCESSORY MODE</span>
                <?php endif; ?>
            </div>
            <p class="text-gray-500">For: <span class="font-bold text-blue-600"><?php echo htmlspecialchars($product['name']); ?></span></p>
        </div>
        <a href="admin_products.php" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded text-sm font-bold transition">← Back to Inventory</a>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-8 bg-gray-50 p-4 rounded-xl border border-gray-200">
        <div class="text-center">
            <p class="text-xs font-bold text-gray-400 uppercase">Master Stock</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $master_stock; ?></p>
        </div>
        <div class="text-center border-l border-gray-200">
            <p class="text-xs font-bold text-gray-400 uppercase">Assigned</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo $total_assigned; ?></p>
        </div>
        <div class="text-center border-l border-gray-200">
            <p class="text-xs font-bold text-gray-400 uppercase">Remaining to Assign</p>
            <p class="text-2xl font-bold <?php echo $remaining_stock == 0 ? 'text-red-500' : 'text-green-600'; ?>">
                <?php echo $remaining_stock; ?>
            </p>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="p-4 mb-6 rounded-lg font-medium <?php echo $msg_type == 'error' ? 'bg-red-100 text-red-700' : ($msg_type == 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'); ?>">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="bg-blue-50 p-6 rounded-xl border border-blue-100 mb-8 relative overflow-hidden">
        
        <?php if($remaining_stock <= 0): ?>
            <div class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center text-center">
                <p class="text-lg font-bold text-gray-800 mb-2">Master Inventory Fully Assigned</p>
                <p class="text-sm text-gray-500 mb-4">You have distributed all <?php echo $master_stock; ?> units.</p>
                <a href="admin_edit_product.php?id=<?php echo $product_id; ?>" class="bg-primary text-blue-600 hover:underline font-bold">Increase Master Stock?</a>
            </div>
        <?php endif; ?>

        <h3 class="font-bold text-gray-700 mb-4 uppercase text-xs tracking-wider">
            <?php echo $is_accessory ? 'Add Accessory Stock' : 'Add New Combination'; ?>
        </h3>
        
        <form method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            
            <?php if(!$is_accessory): ?>
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-gray-500 mb-1">Color</label>
                    <input type="text" name="color" placeholder="e.g. Red" class="w-full p-2 border rounded" required>
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-gray-500 mb-1">Storage</label>
                    <input type="text" name="storage" placeholder="e.g. 128GB" class="w-full p-2 border rounded" required>
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-gray-500 mb-1">RAM</label>
                    <input type="text" name="ram" placeholder="e.g. 6GB" class="w-full p-2 border rounded" required>
                </div>
            <?php else: ?>
                <div class="col-span-3 flex items-center p-2 bg-blue-100 rounded border border-blue-200 text-blue-800 text-sm">
                    <span class="material-icons-round text-sm mr-2">info</span>
                    Specs hidden for accessories. Will save as "Standard".
                </div>
            <?php endif; ?>

            <div class="col-span-1">
                <label class="block text-xs font-bold text-gray-500 mb-1">Price (₦)</label>
                <input type="number" name="price" value="<?php echo $product['price']; ?>" class="w-full p-2 border rounded" required>
            </div>

            <div class="col-span-1">
                <label class="block text-xs font-bold text-gray-500 mb-1">Qty (Max: <?php echo $remaining_stock; ?>)</label>
                <input type="number" name="stock" max="<?php echo $remaining_stock; ?>" placeholder="1" class="w-full p-2 border rounded border-blue-300 bg-white" required>
            </div>

            <div class="col-span-1 flex items-end">
                <button type="submit" name="add_variant" class="w-full bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700 transition">+ Add</button>
            </div>
        </form>
    </div>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="text-xs font-bold text-gray-400 uppercase border-b">
                <th class="py-3">Variant Specification</th>
                <th class="py-3">Price</th>
                <th class="py-3">Stock Allocation</th>
                <th class="py-3 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="text-sm text-gray-700">
            <?php foreach($all_variants as $v): ?>
            <tr class="border-b hover:bg-gray-50 transition">
                <td class="py-3">
                    <?php if($is_accessory): ?>
                        <span class="font-bold text-gray-900">Standard Unit</span>
                    <?php else: ?>
                        <span class="font-bold text-gray-900"><?php echo $v['color']; ?></span> 
                        <span class="text-gray-400 mx-1">|</span> 
                        <?php echo $v['storage']; ?> 
                        <span class="text-gray-400 mx-1">|</span> 
                        <?php echo $v['ram']; ?>
                    <?php endif; ?>
                </td>
                <td class="py-3 text-blue-600 font-bold">₦<?php echo number_format($v['price']); ?></td>
                <td class="py-3">
                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-bold border border-gray-200">
                        <?php echo $v['stock']; ?> Assigned
                    </span>
                </td>
                <td class="py-3 text-right">
                    <a href="?id=<?php echo $product_id; ?>&delete=<?php echo $v['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('Return these <?php echo $v['stock']; ?> units to unassigned stock?');">Remove</a>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(empty($all_variants)): ?>
                <tr><td colspan="4" class="py-6 text-center text-gray-400">No stock assigned yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>
</body>
</html>
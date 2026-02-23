<?php
session_start();
require_once '../db.php'; 

// SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../login.php"); 
    // exit;
}

$message = "";
$error_message = "";

// 1. FETCH ALL PRODUCTS FOR THE DROPDOWN
// This pulls your real inventory so you can link slides to them.
$stmt = $pdo->query("SELECT id, name FROM products WHERE status = 'active' ORDER BY name ASC");
$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. HANDLE SLIDE DELETION
if (isset($_GET['delete'])) {
    $id_to_delete = (int)$_GET['delete'];
    
    // First, get the image name so we can delete the file from the server
    $stmt = $pdo->prepare("SELECT image FROM carousel_slides WHERE id = ?");
    $stmt->execute([$id_to_delete]);
    $slide = $stmt->fetch();
    
    if ($slide && file_exists("../assets/images/" . $slide['image'])) {
        unlink("../assets/images/" . $slide['image']);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM carousel_slides WHERE id = ?");
    $stmt->execute([$id_to_delete]);
    $message = "Slide deleted successfully.";
}

// 3. HANDLE NEW SLIDE UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slide'])) {
    
    // SAFETY CHECK: Ensure a product was actually selected
    $product_id = $_POST['product_id'] ?? null;

    if (!$product_id) {
        $error_message = "❌ Error: You must select a Real Product from the dropdown list.";
    } else {
        // Process Image Upload
        $image_name = "";
        if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
            $ext = pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION);
            $image_name = 'slide_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['slide_image']['tmp_name'], "../assets/images/" . $image_name);
        }

        // Insert into Database
        try {
            $sql = "INSERT INTO carousel_slides (image, title, topic, short_desc, product_id, long_desc, spec_time, spec_port, spec_os, spec_bt, spec_control) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $image_name,
                $_POST['title'],
                $_POST['topic'],
                $_POST['short_desc'],
                $product_id, // Safely captured ID
                $_POST['long_desc'],
                $_POST['spec_time'],
                $_POST['spec_port'],
                $_POST['spec_os'],
                $_POST['spec_bt'],
                $_POST['spec_control']
            ]);
            
            $message = "✅ New slide connected to product successfully!";
        } catch (PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }
    }
}

// 4. FETCH CURRENT SLIDES (Joined with products table to get real names)
$sql_slides = "SELECT c.*, p.name as real_product_name 
               FROM carousel_slides c 
               LEFT JOIN products p ON c.product_id = p.id 
               ORDER BY c.id ASC";
$stmt = $pdo->query($sql_slides);
$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manage Carousel - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <header class="bg-white shadow-sm p-6 mb-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 flex items-center gap-1 font-bold">
                <span class="material-icons-round">arrow_back</span> Back to Dashboard
            </a>
            <h1 class="text-2xl font-bold">Manage Carousel Slides</h1>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-fit">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2"><span class="material-icons-round text-blue-600">add_photo_alternate</span> Add New Slide</h2>
            
            <?php if($message): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-sm font-medium"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if($error_message): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm font-medium"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-4 text-sm">
                
                <div>
                    <label class="block font-bold mb-1">Slide Image</label>
                    <input type="file" name="slide_image" required class="w-full border p-2 rounded-lg">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block font-bold mb-1">Top Title</label><input type="text" name="title" class="w-full border p-2 rounded-lg" required></div>
                    <div><label class="block font-bold mb-1">Topic</label><input type="text" name="topic" class="w-full border p-2 rounded-lg" required></div>
                </div>

                <div><label class="block font-bold mb-1">Short Description</label><textarea name="short_desc" class="w-full border p-2 rounded-lg" rows="2" required></textarea></div>

                <div>
                    <label class="block font-bold mb-1">Link to Real Product <span class="text-red-500">*</span></label>
                    <select name="product_id" class="w-full border-2 border-blue-200 p-2 rounded-lg bg-blue-50 text-blue-800 font-bold focus:border-blue-600" required>
                        <option value="" disabled selected>-- You MUST Select a Product --</option>
                        <?php foreach($all_products as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>">
                                <?php echo htmlspecialchars($prod['name']); ?> (ID: <?php echo $prod['id']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div><label class="block font-bold mb-1">Long Description</label><textarea name="long_desc" class="w-full border p-2 rounded-lg" rows="3" required></textarea></div>

                <div class="grid grid-cols-2 gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="col-span-2 font-bold text-gray-500 uppercase text-xs">Technical Specs</p>
                    <input type="text" name="spec_time" placeholder="Battery (e.g. 6 hours)" class="border p-2 rounded text-xs">
                    <input type="text" name="spec_port" placeholder="Port (e.g. Type-C)" class="border p-2 rounded text-xs">
                    <input type="text" name="spec_os" placeholder="OS (e.g. Android)" class="border p-2 rounded text-xs">
                    <input type="text" name="spec_bt" placeholder="Bluetooth (e.g. 5.3)" class="border p-2 rounded text-xs">
                    <input type="text" name="spec_control" placeholder="Control (e.g. Touch)" class="col-span-2 border p-2 rounded text-xs">
                </div>

                <button type="submit" name="add_slide" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 shadow-md">Upload & Connect Product</button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <?php if(empty($slides)): ?>
                <div class="text-center p-12 bg-gray-100 rounded-2xl text-gray-500 font-medium border-2 border-dashed border-gray-300">
                    No slides active. Add your first slide on the left.
                </div>
            <?php else: ?>
                <?php foreach($slides as $slide): ?>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-6 relative overflow-hidden group">
                    <div class="w-32 h-32 bg-gray-50 rounded-xl flex items-center justify-center p-2 flex-shrink-0 border border-gray-200">
                        <img src="../assets/images/<?php echo $slide['image']; ?>" class="w-full h-full object-contain mix-blend-multiply">
                    </div>

                    <div class="flex-1">
                        <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-md mb-2 inline-block"><?php echo $slide['topic']; ?></span>
                        
                        <h3 class="text-xl font-bold"><?php echo htmlspecialchars($slide['real_product_name'] ?? 'Product Unlinked!'); ?></h3>
                        
                        <p class="text-gray-500 text-sm line-clamp-2 mt-1"><?php echo htmlspecialchars($slide['long_desc']); ?></p>
                        
                        <div class="flex gap-2 mt-3 text-xs text-gray-400 font-mono">
                            <span class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($slide['spec_time']); ?></span>
                            <span class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($slide['spec_port']); ?></span>
                            <span class="bg-gray-100 px-2 py-1 rounded">BT <?php echo htmlspecialchars($slide['spec_bt']); ?></span>
                        </div>
                    </div>

                    <a href="admin_carousel.php?delete=<?php echo $slide['id']; ?>" onclick="return confirm('Delete this slide forever?')" class="absolute top-4 right-4 bg-red-50 text-red-600 p-2 rounded-lg hover:bg-red-600 hover:text-white transition">
                        <span class="material-icons-round">delete</span>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
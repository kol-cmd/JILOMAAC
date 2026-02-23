<?php
session_start();
require_once 'db.php'; 

// 1. Get ID & Fetch Product
if (!isset($_GET['id'])) { header("Location: products.php"); exit; }
$product_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// --- SMART BREADCRUMB LOGIC ---
// Check the category to determine where the "Back" button should go
$back_url = 'products.php'; // Default fallback

if (isset($product['category']) && strtolower($product['category']) === 'accessories') {
    $back_url = 'accesories.php';
}
// ------------------------------

if (!$product) { header("Location: products.php"); exit; }

// --- REVIEW LOGIC START ---
$review_msg = "";
if (isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php"); exit;
    }
    
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    $check = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $check->execute([$user_id, $product_id]);
    
    if ($check->rowCount() > 0) {
        $review_msg = "⚠️ You have already reviewed this product.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$product_id, $user_id, $rating, $comment])) {
            $review_msg = "✅ Review submitted successfully!";
        }
    }
}

// Fetch Reviews & Avg
$stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name, u.picture as user_pic 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
try {
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $sum = 0;
    foreach($reviews as $r) $sum += $r['rating'];
    $avg_rating = round($sum / $total_reviews, 1);
}
// --- REVIEW LOGIC END ---

// 2. Fetch Variants
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC");
$stmt->execute([$product['id']]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Cart Count Logic
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $qty = is_array($item) ? ($item['quantity'] ?? 1) : $item;
        $cart_count += $qty;
    }
}

// 4. User Avatar Logic
$user_picture = $_SESSION['user_picture'] ?? null;
$user_initial = isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo htmlspecialchars($product['name']); ?> - JILOMAAC</title>
    
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#645bff", 
                        secondary: "#EF4444", 
                        "background-light": "#F9FAFB", 
                        "background-dark": "#111827", 
                        "surface-light": "#FFFFFF", 
                        "surface-dark": "#1F2937", 
                    },
                    fontFamily: { display: ["Inter", "sans-serif"] },
                    borderRadius: { DEFAULT: "0.5rem", 'xl': '1rem', '2xl': '1.5rem', '3xl': '2rem' },
                    boxShadow: { 'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)' }
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        select { background-image: none; }
        
        /* Star Rating CSS */
        .rate { float: left; height: 46px; padding: 0 10px; }
        .rate:not(:checked) > input { position:absolute; top:-9999px; }
        .rate:not(:checked) > label { float:right; width:1em; overflow:hidden; white-space:nowrap; cursor:pointer; font-size:30px; color:#ccc; }
        .rate:not(:checked) > label:before { content: '★ '; }
        .rate > input:checked ~ label { color: #ffc700; }
        .rate:not(:checked) > label:hover,
        .rate:not(:checked) > label:hover ~ label { color: #deb217; }
    </style>
    <link rel="stylesheet" href="assets/css/styled.css" />
</head>
<body class="bg-background-light dark:bg-background-dark text-gray-900 dark:text-white transition-colors duration-300 min-h-screen flex flex-col">

<header class="sticky top-0 z-50 bg-surface-light/90 dark:bg-surface-dark/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-700">
    <nav id="mainNav">
        <div class="left">
          <div class="tall-text">
            <section class="JIL">JIL</section>
            <div class="highlights"><img src="assets/images/Adobe Express - file.png" alt="" /></div>
            <div><section class="MAAC">MAAC</section></div>
          </div>
        </div>
        <div class="right">
          <h4 class="reflink"><a href="index.php">Home</a></h4>
          <h4 class="reflink"><a href="accesories.php">Accesories</a></h4>
          <h4 class="reflink active"><div class="shop"><a href="products.php">Products</a></div></h4>
          <h4 class="reflink">Deals</h4>
          <h4 class="reflink">About</h4>
        </div>
        
        <div class="rightmost">
          <form class="search" id="searchForm" action="products.php" method="GET">
            <div class="svgcon">
                <button type="submit" style="background:none; border:none; cursor:pointer;">
                    <h4 class="reflink"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/></svg></h4>
                </button>
            </div>
            <div class="bar"><input type="text" id="searchInput" name="search" placeholder="Search..." autocomplete="off" /></div>
            <div class="x">X</div>
          </form>

          <div class="cart">
            <h4 class="reflink">
              <a href="cart.php" style="display: flex; align-items: center; position: relative;">
                <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px" fill="#e3e3e3"><path d="M280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM246-720l96 200h280l110-200H246Zm-38-80h590q23 0 35 20.5t1 41.5L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68-39.5t-2-78.5l54-98-144-304H40v-80h130l38 80Zm134 280h280-280Z"/></svg>
                
                <?php if($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
              </a>
            </h4>
          </div>
          
        <div class="profile-container">
            <div class="avatar-trigger">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['user_picture']) && !empty($_SESSION['user_picture'])): ?>
                        <img src="<?php echo $_SESSION['user_picture']; ?>" class="user-avatar" alt="Profile">
                    <?php else: ?>
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="status-dot"></div>
                <?php else: ?>
                     <a href="login.php" style="display:flex; height:100%; align-items:center; justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#555"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-32q0-34 17.5-62.5T224-304q55-32 121-51t135-19q69 0 135 19t121 51q29 17 46.5 45.5T800-192v32H160Z"/></svg>
                    </a>
                <?php endif; ?>
            </div>

            <?php if(isset($_SESSION['user_id'])): ?>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    <div class="user-avatar big-avatar">
                        <?php if(isset($_SESSION['user_picture']) && !empty($_SESSION['user_picture'])): ?>
                            <img src="<?php echo $_SESSION['user_picture']; ?>" style="border-radius:50%; width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        <?php endif; ?>
                        <div class="status-dot"></div> </div>
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                </div>
                <div class="dropdown-linked">
                    <a href="my_orders.php">My Orders</a>
                    
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" style="color: #645bff;">Admin Dashboard</a>
                    <?php endif; ?>
                    <div class="sign-out-area">
                        <a href="logout.php">Sign Out</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
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
            <li>
                <a href="index.php" class="mobile-nav-item">
                    <span>Home</span>
                </a>
            </li>
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
            <li>
                <a href="deals.html" class="mobile-nav-item">
                    <span>Exclusive Deals</span>
                </a>
            </li>
            <li>
                <a href="about.html" class="mobile-nav-item">
                    <span>Our Story</span>
                </a>
            </li>
        </ul>

        <div class="mobile-nav-footer">
           
                <a href="cart.php" class="mobile-cart-btn">
                <span class="material-icons-round">shopping_cart</span>
                <span>View Cart (<?php echo $cart_count; ?>)</span>
            </a>
           
            
            <div class="mobile-login-area">
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="mobile-login-link">Log in to your account</a>
                <?php else: ?>
                    <span style="color: #6b7280; font-size: 1rem;">Logged in as <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</header>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12 w-full">
    
    <div class="flex items-center mb-8 text-sm text-gray-500 dark:text-gray-400">
        <a class="flex items-center hover:text-primary transition-colors" href="<?php echo $back_url; ?>">
            <span class="material-icons-round text-lg mr-1">arrow_back</span> Back
        </a>
        <span class="mx-2 text-gray-300 dark:text-gray-600">/</span>
        <a href="<?php echo $back_url; ?>">
            <span class="text-gray-900 dark:text-white font-medium hover:text-primary transition-colors">
                <?php echo htmlspecialchars($product['category'] ?? 'Product'); ?>
            </span>
        </a>
        <span class="mx-2 text-gray-300 dark:text-gray-600">/</span>
        <span class="text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <?php if(isset($_GET['error'])): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 font-medium flex items-center gap-3">
            <span class="material-icons-round">error_outline</span>
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['msg'])): ?>
        <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-300 font-medium flex items-center gap-3">
            <span class="material-icons-round">check_circle</span>
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 xl:gap-20">
        
        <div class="flex flex-col gap-4">
            <div class="relative bg-white dark:bg-surface-dark rounded-3xl aspect-[4/3] lg:aspect-square flex items-center justify-center p-8 shadow-soft border border-gray-100 dark:border-gray-700 overflow-hidden group">
                <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" class="w-auto h-3/4 object-contain transition-transform duration-500 group-hover:scale-105" alt="Product Image"/>
            </div>
            <div class="grid grid-cols-4 gap-4">
                <button class="aspect-square rounded-xl border-2 border-primary bg-white dark:bg-surface-dark p-2 flex items-center justify-center">
                    <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" class="h-full object-contain"/>
                </button>
            </div>
        </div>

        <div class="flex flex-col">
            <div class="mb-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="bg-secondary/10 text-secondary text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1">
                                <span class="material-icons-round text-sm">local_offer</span> In Stock
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">SKU: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="product-price text-3xl font-bold text-primary">₦<?php echo number_format($product['price']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <div class="flex text-yellow-400">
                            <?php 
                            for($i=1; $i<=5; $i++) {
                                echo ($i <= round($avg_rating)) ? '<span class="material-icons-round text-sm">star</span>' : '<span class="material-icons-round text-sm text-gray-300 dark:text-gray-600">star</span>';
                            }
                            ?>
                        </div>
                        <span class="font-bold text-lg"><?php echo $avg_rating; ?></span>
                    </div>
                    <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>
                    <a href="#reviews" class="text-gray-500 hover:text-primary transition-colors text-sm font-medium underline decoration-gray-300 underline-offset-4">
                        <?php echo $total_reviews; ?> reviews
                    </a>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Description</h3>
                <p class="text-gray-600 dark:text-gray-300 leading-relaxed text-base line-clamp-3">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </p>
            </div>

            <form action="cart_action.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                <input type="hidden" name="product_image" value="<?php echo $product['image']; ?>">
                <input type="hidden" name="add_to_cart" value="1">

                <div class="mb-8">
                    <div class="flex justify-between items-end mb-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Configuration</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Select Options</span>
                    </div>

                    <?php if(empty($variants)): ?>
                        <div class="p-4 bg-red-50 text-red-500 rounded-lg text-sm font-bold border border-red-100">
                            Currently Out of Stock (No configurations available)
                        </div>
                    <?php else: ?>
                        <div class="relative">
                            <select name="variant_id" id="variantSelect" required onchange="updateDetails(this)" 
                                class="block w-full pl-4 pr-10 py-4 text-base border-gray-200 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm rounded-2xl bg-white dark:bg-surface-dark text-gray-900 dark:text-white cursor-pointer shadow-sm appearance-none">
                                
                                <option value="" disabled selected>Choose Options...</option>
                                
                                <?php 
                                    // Count active stock variants
                                    $active_vars = 0;
                                    foreach($variants as $v) if($v['stock'] > 0) $active_vars++; 
                                ?>

                                <?php foreach($variants as $v): ?>
                                    <?php 
                                        $stock = $v['stock'];
                                        $price = number_format($v['price']);
                                        
                                        // --- CLEANER LABEL LOGIC ---
                                        $parts = [];
                                        // Ignore 'Standard' and '-' for accessories
                                        if ($v['color'] !== 'Standard' && $v['color'] !== '') $parts[] = $v['color'];
                                        if ($v['storage'] !== '-' && $v['storage'] !== '') $parts[] = $v['storage'];
                                        if ($v['ram'] !== '-' && $v['ram'] !== '') $parts[] = $v['ram'];
                                        
                                        // If parts is empty (Accessory), just show "Standard Unit"
                                        $spec = empty($parts) ? "Standard Unit" : implode(' | ', $parts);
                                        
                                        $disabled = ($stock <= 0) ? 'disabled' : '';
                                        $label = ($stock <= 0) ? "$spec — (Sold Out)" : "$spec — ₦$price";
                                        
                                        // Auto-select if it's the only option available
                                        $selected = ($active_vars === 1 && $stock > 0) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $v['id']; ?>" data-price="<?php echo $v['price']; ?>" data-stock="<?php echo $stock; ?>" <?php echo $disabled; ?> <?php echo $selected; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <span class="material-icons-round text-gray-400">expand_more</span>
                            </div>
                        </div>
                        <div id="stockMessage" class="mt-2 text-sm font-medium min-h-[20px]"></div>
                    <?php endif; ?>
                </div>

                <div class="flex items-end gap-4 mt-auto pt-6 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">Qty</label>
                        <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-full bg-white dark:bg-surface-dark h-14 px-2">
                            <button type="button" onclick="changeQty(-1)" class="p-2 hover:text-primary text-gray-500 dark:text-gray-400"><span class="material-icons-round">remove</span></button>
                            <input type="number" name="quantity" id="qtyInput" value="1" min="1" class="w-12 text-center bg-transparent border-none p-0 text-gray-900 dark:text-white font-bold focus:ring-0">
                            <button type="button" onclick="changeQty(1)" class="p-2 hover:text-primary text-gray-500 dark:text-gray-400"><span class="material-icons-round">add</span></button>
                        </div>
                    </div>
                    <button type="submit" id="addBtn" <?php echo empty($variants) ? 'disabled' : ''; ?>
                        class="flex-1 h-14 bg-primary hover:bg-gray-600 text-white font-bold text-lg rounded-full shadow-lg shadow-grey-500/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2 disabled:bg-gray-400 disabled:cursor-not-allowed disabled:transform-none">
                        <span class="material-icons-round">shopping_cart</span> Add to Cart
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="reviews" class="mt-20 border-t border-gray-200 dark:border-gray-700 pt-10">
        <h2 class="text-2xl font-bold mb-8 text-gray-900 dark:text-white">Customer Reviews</h2>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-surface-dark p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 sticky top-24">
                    <h3 class="font-bold text-lg mb-4 text-gray-900 dark:text-white">Write a Review</h3>
                    <?php if($review_msg): ?>
                        <div class="p-3 mb-4 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 text-sm font-bold"><?php echo $review_msg; ?></div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Rating</label>
                                <div class="rate">
                                    <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="5 stars">5 stars</label>
                                    <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars">4 stars</label>
                                    <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars">3 stars</label>
                                    <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars">2 stars</label>
                                    <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star">1 star</label>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Review</label>
                                <textarea name="comment" rows="4" class="w-full rounded-xl bg-gray-50 dark:bg-gray-900 border-none p-3 text-sm focus:ring-2 focus:ring-primary text-gray-900 dark:text-white" placeholder="How was the product?" required></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="w-full bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold py-3 rounded-xl hover:opacity-80 transition shadow-lg">Submit Review</button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <p class="text-gray-500 mb-4">Please login to write a review.</p>
                            <a href="login.php" class="text-primary font-bold hover:underline">Login Now</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <?php if(empty($reviews)): ?>
                    <div class="text-center py-10 text-gray-500">
                        <span class="material-icons-round text-4xl mb-2 text-gray-300">rate_review</span>
                        <p>No reviews yet. Be the first to write one!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($reviews as $r): ?>
                        <div class="flex gap-4 p-6 bg-white dark:bg-surface-dark rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                            <div class="flex-shrink-0">
                                <?php if(isset($r['user_pic']) && !empty($r['user_pic'])): ?>
                                    <img src="<?php echo $r['user_pic']; ?>" class="w-12 h-12 rounded-full object-cover border-2 border-white dark:border-gray-600 shadow-sm">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-lg">
                                        <?php echo strtoupper(substr($r['user_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($r['user_name']); ?></h4>
                                        <span class="text-xs text-gray-400">• <?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                                    </div>
                                    
                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $r['user_id']): ?>
                                        <form action="delete_review.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                            <button type="submit" name="delete_review" class="text-xs text-red-500 hover:text-red-700 font-bold hover:underline flex items-center gap-1">
                                                <span class="material-icons-round text-sm">delete</span> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="flex text-yellow-400 mb-2">
                                    <?php for($i=1; $i<=5; $i++) {
                                        echo ($i <= $r['rating']) ? '<span class="material-icons-round text-sm">star</span>' : '<span class="material-icons-round text-sm text-gray-300 dark:text-gray-600">star</span>';
                                    } ?>
                                </div>
                                <p class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed">
                                    <?php echo htmlspecialchars($r['comment']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</main>

<footer>
    <div class="footer-content">
        <div class="footer-section"><h3>JILOMAAC</h3><p>Original Phones. Smart Prices.</p></div>
        <div class="footer-section"><h4>Quick Links</h4><a href="index.php">Home</a><a href="products.php">Phones</a><a href="#">Warranty Policy</a></div>
        <div class="footer-section"><h4>Stay Updated</h4><input type="email" placeholder="Enter your email" /><button>Subscribe</button></div>
    </div>
    <div class="footer-bottom">&copy; 2024 JILOMAAC. All rights reserved.</div>
</footer>

<script>
    // AUTO-RUN ON LOAD to handle auto-selected accessories
    window.addEventListener('DOMContentLoaded', (event) => {
        const select = document.getElementById('variantSelect');
        if(select && select.value) {
            updateDetails(select); // Trigger the update immediately if one is selected
        }
    });

    function updateDetails(select) {
        const option = select.options[select.selectedIndex];
        const price = option.getAttribute('data-price');
        const stock = parseInt(option.getAttribute('data-stock'));
        const messageBox = document.getElementById('stockMessage');
        const priceDisplay = document.querySelector('.product-price');
        const addBtn = document.getElementById('addBtn');

        if (price) {
            priceDisplay.innerText = '₦' + parseInt(price).toLocaleString();
            if (stock > 0) {
                messageBox.innerHTML = `<span class="text-primary flex items-center gap-1"><span class="material-icons-round text-sm">check_circle</span> In Stock: ${stock} units available.</span>`;
                addBtn.disabled = false;
                addBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                addBtn.classList.add('bg-primary', 'hover:bg-black-600');
                addBtn.innerHTML = '<span class="material-icons-round">shopping_cart</span> Add to Cart';
            } else {
                messageBox.innerHTML = `<span class="text-secondary flex items-center gap-1"><span class="material-icons-round text-sm">cancel</span> Currently Out of Stock.</span>`;
                addBtn.disabled = true;
                addBtn.classList.remove('bg-primary', 'hover:bg-black-600');
                addBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
                addBtn.innerText = "Sold Out";
            }
        }
    }

    function changeQty(amount) {
        const input = document.getElementById('qtyInput');
        let current = parseInt(input.value);
        let newVal = current + amount;
        if (newVal >= 1) {
            input.value = newVal;
        }
    }
</script>
<script src="assets/js/script.js"></script>

</body>
</html>
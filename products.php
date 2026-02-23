<?php
session_start();
require_once 'db.php';

// 1. CAPTURE INPUTS
$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? ''; 
$sort = $_GET['sort'] ?? 'newest';

// 2. BRAND CONFIGURATION
$brand_data = [
    'Tecno' => [
        'logo' => 'tecno-mobile-logo-icon.webp', 
        'banner' => 'ng11pc.webp', 
        'desc' => 'Stop at Nothing.',
        'color' => 'text-blue-600'
    ],
    'Infinix' => [
        'logo' => 'infinix logo.jpg', 
        'banner' => 'infinxbanner_pc_1.webp', 
        'desc' => 'The Future is Now.',
        'color' => 'text-green-500'
    ],
    'Itel' => [
        'logo' => 'Itel-mobile-logo-vector.svg.png', 
        'banner' => 'S26_PC_2416x800.png', 
        'desc' => 'Enjoy Better Life.',
        'color' => 'text-red-500'
    ],
    'Oraimo' => [
        'logo' => 'oraimo-logo-png_seeklogo-514471.png', 
        'banner' => 'oraimo banner.jpg', 
        'desc' => 'Keep Exploring.',
        'color' => 'text-green-600'
    ],
];

// 3. BASE SQL QUERY
$sql = "SELECT p.*, 
        (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
        FROM products p 
        WHERE (name LIKE ? OR description LIKE ?) 
        AND status = 'active'";

$params = ["%$search%", "%$search%"];

// 4. APPLY FILTERS
if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if ($brand) {
    $sql .= " AND name LIKE ?"; 
    $params[] = "%$brand%";
}

// 5. APPLY SORTING
switch ($sort) {
    case 'price_asc': $sql .= " ORDER BY price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY price DESC"; break;
    case 'hot': $sql .= " ORDER BY avg_rating DESC, id DESC"; break;
    case 'bestselling': $sql .= " ORDER BY review_count DESC, id DESC"; break;
    case 'newest': default: $sql .= " ORDER BY id DESC"; break;
}

// 6. EXECUTE
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cart Count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $qty = is_array($item) ? ($item['quantity'] ?? 1) : $item;
        $cart_count += $qty;
    }
}
// 7. FETCH ANNOUNCEMENT
$announce = null;
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $announce = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Products - JILOMAAC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#645bff",
                        secondary: "#EF4444",
                        "card-dark": "#1E1E1E",
                    },
                    fontFamily: { sans: ["Plus Jakarta Sans", "sans-serif"] },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
    <link rel="stylesheet" href="assets/css/styled.css" />
</head>
<body class="bg-gray-50 dark:bg-[#121212] text-gray-900 dark:text-white transition-colors duration-300 min-h-screen flex flex-col">
<?php if ($announce && $announce['is_active'] == 1 && !empty($announce['announcement_text'])): ?>
      <div class="announcement-bar">
        🚀 <?php echo htmlspecialchars($announce['announcement_text']); ?>
      </div>
    <?php endif; ?>
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
          <div class="search" id="searchForm">
    <div class="svgcon">
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/></svg>
    </div>
    <div class="bar">
        <input type="text" id="searchInput" name="q" placeholder="Search..." autocomplete="off" value="<?php echo htmlspecialchars($search); ?>" />
    </div>
    <div class="x">X</div>
</div>
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
                        <svg xmlns="http://www.w3.org/2000/svg" height="35px" viewBox="0 -960 960 960" width="35px" fill="#e3e3e3"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg>
                        
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
            <?php if ($announce && $announce['is_active'] == 1 && !empty($announce['announcement_text'])): ?>
            <div class="announcement-bar mobile-announce">
                🚀 <?php echo htmlspecialchars($announce['announcement_text']); ?>
            </div>
            <?php endif; ?>

            <div class="mobile-nav-header">
                <div class="left mobile-nav-title">
                    <div class="tall-text">
                        <section class="JIL" style="color: white;">JIL</section>
                        <div class="highlights"><img src="assets/images/Adobe Express - file.png" alt="" /></div>
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
    <main class="max-w-[1400px] mx-auto px-6 py-10 w-full min-h-[90vh]">
        
        <header class="mb-8 flex flex-col lg:flex-row lg:items-end justify-between gap-8">
            <div>
                <h1 id="featuredTitle" class="text-4xl lg:text-5xl font-black mb-3 tracking-tight text-gray-900 dark:text-white">
                    <?php echo $brand ? $brand . ' Store' : 'All Products'; ?>
                </h1>
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    <?php echo $brand ? "Browse the latest devices from $brand." : "Browse our collection of Original Transsion Phones and Accessories."; ?>
                </p>
            </div>
            
            <form action="" method="GET" class="relative group w-full sm:w-48 z-20">
                <?php if($category): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>"><?php endif; ?>
                <?php if($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                <?php if($brand): ?><input type="hidden" name="brand" value="<?php echo htmlspecialchars($brand); ?>"><?php endif; ?>
                
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="material-icons-round text-gray-400 text-sm">sort</span>
                </div>
                <select name="sort" onchange="this.form.submit()" class="appearance-none w-full bg-white dark:bg-card-dark border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 py-3 pl-10 pr-8 rounded-xl leading-tight focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary cursor-pointer font-bold text-sm">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                    <option value="bestselling" <?php echo $sort == 'bestselling' ? 'selected' : ''; ?>>Best Selling</option>
                    <option value="hot" <?php echo $sort == 'hot' ? 'selected' : ''; ?>>Hot Deals</option>
                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                </div>
            </form>
        </header>

        <section class="mb-12">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-icons-round text-gray-400 text-lg">grid_view</span>
                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Shop By Brand</h3>
            </div>
            
           <div class=" border 3px solid; -mx-6 pl-6 pr-6 md:mx-0 md:px-0 flex overflow-x-auto pt-4 pb-8 gap-3 md:gap-5 no-scrollbar snap-x md:justify-center">
                
                <a href="products.php?category=<?php echo $category; ?>&sort=<?php echo $sort; ?>" 
                   class="snap-start flex-shrink-0 w-28 h-28 md:w-40 md:h-36 rounded-3xl border-2 flex flex-col items-center justify-center gap-3 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl
                   <?php echo $brand == '' ? 'bg-gray-900 border-gray-900 text-white shadow-lg scale-105' : 'bg-white dark:bg-card-dark border-gray-100 dark:border-gray-800 text-gray-400 grayscale hover:grayscale-0'; ?>">
                    <span class="material-icons-round text-3xl md:text-4xl">storefront</span>
                    <span class="font-bold text-xs md:text-sm">All Brands</span>
                </a>

                <?php foreach($brand_data as $b_name => $b_info): ?>
    <a href="products.php?brand=<?php echo $b_name; ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>" 
       class="snap-start flex-shrink-0 w-28 h-28 md:w-40 md:h-36 bg-white dark:bg-card-dark rounded-3xl border-2 flex items-center justify-center transition-all duration-300 hover:-translate-y-1 hover:shadow-xl relative overflow-hidden group
       <?php echo $brand == $b_name 
           // ACTIVE STATE: Clean border + Subtle background tint + Shadow (No Rings)
           ? 'border-primary bg-primary/5 shadow-lg shadow-primary/20 scale-[1.02]' 
           // INACTIVE STATE: Subtle gray border
           : 'border-gray-100 dark:border-gray-800 hover:border-gray-200'; 
       ?>">
        
        <img src="assets/images/<?php echo $b_info['logo']; ?>" 
             alt="<?php echo $b_name; ?>" 
             class="w-[70%] h-[70%] object-contain transition-transform duration-500 group-hover:scale-110">
             
    </a>
<?php endforeach; ?>
            </div>
        </section>

        <?php if($brand && isset($brand_data[$brand])): 
            $active_brand = $brand_data[$brand];
        ?>
            <section class="mb-16 relative overflow-hidden rounded-[2.5rem] bg-gray-900 min-h-[250px] md:min-h-[350px] flex items-center shadow-2xl group">
                <img src="assets/images/<?php echo $active_brand['banner']; ?>" 
                     class="absolute inset-0 w-full h-full object-cover object-center transition-transform duration-700 group-hover:scale-105" 
                     alt="<?php echo $brand; ?> Banner">
                
                <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/40 to-transparent"></div>

                <div class="relative z-10 p-8 md:p-16 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 mb-4 md:mb-6 shadow-lg">
                        <span class="material-icons-round text-yellow-400 text-xs md:text-sm">verified</span>
                        <span class="text-[10px] md:text-xs font-bold text-white uppercase tracking-wider">Official Store</span>
                    </div>
                    
                    <h2 class="text-3xl md:text-7xl font-black text-white mb-2 md:mb-4 tracking-tight drop-shadow-lg"><?php echo $brand; ?></h2>
                    <p class="text-lg md:text-xl text-gray-100 font-medium mb-6 md:mb-8 italic drop-shadow-md">"<?php echo $active_brand['desc']; ?>"</p>
                    
                    <button onclick="document.getElementById('productGrid').scrollIntoView({behavior: 'smooth'})" class="bg-white text-gray-900 px-6 py-2.5 md:px-8 md:py-3 rounded-full font-bold hover:bg-gray-100 transition-colors flex items-center gap-2 shadow-xl hover:shadow-2xl text-sm md:text-base hover:-translate-y-1 transform duration-300">
                        View Collection <span class="material-icons-round">arrow_downward</span>
                    </button>
                </div>
            </section>
        <?php endif; ?>

        <?php if(empty($products)): ?>
            <div class="text-center py-20 bg-white dark:bg-card-dark rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                <span class="material-icons-round text-7xl text-gray-200 dark:text-gray-700 mb-6">search_off</span>
                <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
                <a href="products.php" class="inline-block mt-6 text-primary font-bold hover:underline">Clear Filters</a>
            </div>
        <?php else: ?>
            
            <div id="productGrid" class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-10">
                
                <?php foreach($products as $p): ?>
    <?php
        $rating_val = isset($p['avg_rating']) && $p['avg_rating'] > 0 ? round($p['avg_rating'], 1) : 0;
        $rating_text = $rating_val > 0 ? $rating_val : 'New';
        $rating_color = $rating_val > 0 ? 'text-yellow-400' : 'text-gray-300';
        $is_hot = ($rating_val >= 4.5); 
    ?>
    <a href="product_details.php?id=<?php echo $p['id']; ?>" class="group bg-white dark:bg-card-dark rounded-2xl md:rounded-[2.5rem] p-3 md:p-7 pb-4 md:pb-10 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 border border-transparent hover:border-gray-100 dark:hover:border-gray-700 relative flex flex-col h-full text-left no-underline overflow-hidden">
        
        <div class="aspect-square bg-gray-50 dark:bg-subtle-dark rounded-xl md:rounded-[2rem] flex items-center justify-center mb-3 md:mb-8 relative overflow-hidden">
            
            <?php if($p['stock_quantity'] < 5 && $p['stock_quantity'] > 0): ?>
                <span class="absolute top-2 right-2 md:top-5 md:right-5 bg-secondary text-white text-[8px] md:text-[10px] uppercase font-black px-2 py-1 md:px-3 md:py-1.5 rounded-full z-10 tracking-widest">Low Stock</span>
            <?php elseif($is_hot): ?>
                <span class="absolute top-2 right-2 md:top-5 md:right-5 bg-orange-500 text-white text-[8px] md:text-[10px] uppercase font-black px-2 py-1 md:px-3 md:py-1.5 rounded-full z-10 tracking-widest flex items-center gap-1">
                    <span class="material-icons-round text-[10px] md:text-[12px]">local_fire_department</span> Hot
                </span>
            <?php endif; ?>
            
            <img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" class="w-[85%] h-[85%] object-contain mix-blend-multiply dark:mix-blend-normal filter dark:brightness-90 transition-transform duration-700 group-hover:scale-110" alt="<?php echo htmlspecialchars($p['name']); ?>">
        </div>

        <div class="px-1 md:px-2 flex flex-col flex-grow">
            <div class="flex justify-between items-start mb-2 md:mb-4">
                <h3 class="text-sm md:text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white group-hover:text-primary transition-colors font-sans m-0 leading-tight line-clamp-2 min-h-[2.2rem] md:min-h-[3.6rem]">
                    <?php echo htmlspecialchars($p['name']); ?>
                </h3>
                
                <div class="flex items-center gap-0.5 md:gap-1 bg-gray-50 dark:bg-subtle-dark px-1.5 md:px-3 py-1 md:py-1.5 rounded-lg md:rounded-xl ml-2 shrink-0">
                    <span class="material-icons-round <?php echo $rating_color; ?> text-xs md:text-sm">star</span>
                    <span class="text-xs md:text-sm font-bold text-gray-800 dark:text-gray-200"><?php echo $rating_text; ?></span>
                </div>
            </div>
            
            <p class="text-xs md:text-base text-gray-500 dark:text-gray-400 mb-3 md:mb-8 line-clamp-2 flex-grow leading-relaxed hidden sm:block">
                <?php echo htmlspecialchars(substr($p['description'], 0, 80)) . '...'; ?>
            </p>

            <div class="flex items-center justify-between mt-auto pt-2 md:pt-4 border-t md:border-none border-gray-50">
                <div class="flex flex-col">
                    <span class="text-[9px] md:text-xs uppercase tracking-[0.15em] text-gray-400 font-bold mb-0.5">Starting at</span>
                    
                    <div class="flex items-baseline text-gray-900 dark:text-white leading-none">
                        <span class="text-lg md:text-3xl font-extrabold mr-0.5 font-sans">₦</span>
                        <span class="text-xl md:text-3xl lg:text-4xl font-black tracking-tighter font-sans">
                            <?php echo number_format($p['price']); ?>
                        </span>
                    </div>
                </div>
                <div class="w-8 h-8 md:w-14 md:h-14 rounded-full bg-primary text-white flex items-center justify-center hover:bg-[#4e44e6] transition-all duration-300 shadow-lg md:shadow-xl shadow-primary/40 transform active:scale-90">
                    <span class="material-icons-round text-lg md:text-3xl">add</span>
                </div>
            </div>
        </div>
    </a>
<?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <footer>
        <div class="footer-content">
          <div class="footer-section"><h3>JILOMAAC</h3><p>Original Phones. Smart Prices.</p></div>
          <div class="footer-section"><h4>Quick Links</h4><a href="index.php">Home</a><a href="products.php">Phones</a><a href="#">Warranty Policy</a></div>
          <div class="footer-section"><h4>Stay Updated</h4><input type="email" placeholder="Enter your email" /><button>Subscribe</button></div>
        </div>
        <div class="footer-bottom">&copy; 2024 JILOMAAC. All rights reserved.</div>
      </footer>
    <script src="assets/js/script.js"></script>
    

</body>
</html>
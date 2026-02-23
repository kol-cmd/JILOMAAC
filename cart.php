<?php
session_start();

// 1. CONNECT TO DATABASE
if (file_exists('admin/db.php')) { require_once 'admin/db.php'; }
elseif (file_exists('db.php')) { require_once 'db.php'; }
elseif (file_exists('../db.php')) { require_once '../db.php'; }

// 2. FETCH ANNOUNCEMENT
$announce = null;
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $announce = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// 3. CART LOGIC
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_count = 0;
$subtotal = 0;

if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        $qty = $item['quantity'] ?? 1;
        $price = $item['price'] ?? 0;
        $subtotal += ($price * $qty);
        $cart_count += $qty;
    }
}

// 4. TOTAL
$total = $subtotal; // No shipping yet

// 5. FETCH "YOU MIGHT ALSO LIKE"
$recommendations = [];
if (isset($pdo)) {
    $ids_in_cart = [];
    foreach($cart_items as $key => $val) {
        // Extract Product ID from keys like "v_10" or "p_5"
        if(isset($val['id'])) $ids_in_cart[] = $val['id'];
    }
    if(empty($ids_in_cart)) $ids_in_cart = [0]; 
    
    $placeholders = implode(',', array_fill(0, count($ids_in_cart), '?'));
    $sql = "SELECT * FROM products WHERE id NOT IN ($placeholders) ORDER BY RAND() LIMIT 4";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids_in_cart);
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// User Initials
$user_initial = isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Cart - JILOMAAC</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#645bff", 
                        "primary-hover": "#2e3b9e",
                        "accent-purple": "#6366f1",
                        "background-light": "#f3f4f6", 
                        "background-dark": "#0a0a0f", 
                        "surface-dark": "#13131f", 
                        "surface-light": "#ffffff",
                    },
                    fontFamily: {
                        display: ["Anton", "sans-serif"], 
                        sans: ["Inter", "sans-serif"],
                    },
                },
            },
        };
    </script>
    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-track { background: #0a0a0f; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }
        
        @keyframes pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .badge-pop { animation: pop 0.3s ease-in-out; }
    </style>
    <link rel="stylesheet" href="assets/css/styled.css" />
</head>
<body class="bg-background-light dark:bg-background-dark text-gray-900 dark:text-gray-100 font-sans antialiased transition-colors duration-300 min-h-screen flex flex-col">

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
          <h4 class="reflink"><a href="products.php">Phones</a></h4>
          <h4 class="reflink"><a href="accesories.php">Accesories</a></h4>
          <h4 class="reflink"><div class="shop"><a href="products.php">Shop</a></div></h4>
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

   <main class="flex-grow container mx-auto px-4 py-8 lg:py-12 min-h-[80vh] flex flex-col">
        <h1 class="text-4xl md:text-5xl font-display uppercase mb-8 text-center md:text-left bg-clip-text text-transparent bg-gradient-to-br from-gray-900 to-gray-500 dark:from-white dark:to-gray-400">
            Your Cart
        </h1>

        <?php if(isset($_GET['error'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-900 text-red-600 dark:text-red-300 font-bold flex items-center gap-2">
                <span class="material-icons-outlined">error</span>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <?php if(empty($cart_items)): ?>
            <div class="text-center py-20 bg-surface-light dark:bg-surface-dark rounded-xl shadow-lg border border-gray-200 dark:border-gray-800">
                <span class="material-icons-outlined text-6xl text-gray-300 dark:text-gray-600 mb-4">shopping_cart_checkout</span>
            
                <p class="text-gray-500 mb-8">Looks like you haven't added any phones yet.</p>
                <a href="products.php" class="bg-primary hover:bg-primary-hover text-white px-8 py-3 rounded-lg font-bold uppercase tracking-wider transition-colors inline-flex items-center gap-2">
                    Start Shopping <span class="material-icons-outlined">arrow_forward</span>
                </a>
            </div>
        <?php else: ?>
            
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                <div class="w-full lg:w-2/3">
                    <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                        
                        <div class="hidden md:grid grid-cols-12 gap-4 p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <div class="col-span-6">Product</div>
                            <div class="col-span-2 text-center">Price</div>
                            <div class="col-span-2 text-center">Quantity</div>
                            <div class="col-span-2 text-right">Total</div>
                        </div>

                        <?php foreach($cart_items as $key => $item): 
                            $img = $item['image'] ?? 'placeholder.jpg';
                            $name = $item['name'] ?? 'Product';
                            $price = $item['price'] ?? 0;
                            $qty = $item['quantity'] ?? 1;
                            $item_subtotal = $price * $qty;
                            
                            // Check for Variant Options (Safe check)
                            $options = $item['options'] ?? [];
                        ?>
                        <div class="group grid grid-cols-1 md:grid-cols-12 gap-4 p-6 items-center border-b border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            
                            <div class="col-span-1 md:col-span-6 flex items-center gap-4">
                                <div class="w-20 h-20 bg-gray-200 dark:bg-gray-800 rounded-lg flex-shrink-0 overflow-hidden relative">
                                    <img src="assets/images/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($name); ?>" class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-900 dark:text-white"><?php echo htmlspecialchars($name); ?></h3>
                                    
                                    <?php if(!empty($options)): ?>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        <?php if(!empty($options['Color'])): ?>
                                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-500 dark:text-gray-300">
                                                Color: <?php echo htmlspecialchars($options['Color']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if(!empty($options['Storage'])): ?>
                                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-500 dark:text-gray-300">
                                                Storage: <?php echo htmlspecialchars($options['Storage']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if(!empty($options['RAM'])): ?>
                                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-500 dark:text-gray-300">
                                                RAM: <?php echo htmlspecialchars($options['RAM']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <a href="cart_action.php?action=remove&id=<?php echo $key; ?>" class="mt-2 text-xs text-red-500 hover:text-red-400 font-medium flex items-center gap-1 opacity-70 hover:opacity-100 transition-opacity">
                                        <span class="material-icons-outlined text-[16px]">delete</span> Remove
                                    </a>
                                </div>
                            </div>

                            <div class="col-span-1 md:col-span-2 text-left md:text-center font-medium text-gray-700 dark:text-gray-300">
                                ₦<?php echo number_format($price); ?>
                            </div>

                            <div class="col-span-1 md:col-span-2 flex justify-start md:justify-center">
                                <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
                                    <a href="cart_action.php?action=update&id=<?php echo $key; ?>&qty=<?php echo max(1, $qty - 1); ?>" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">-</a>
                                    <input type="text" value="<?php echo $qty; ?>" class="w-10 text-center bg-transparent border-none text-sm font-semibold focus:ring-0 p-1 text-gray-900 dark:text-white" readonly/>
                                    <a href="cart_action.php?action=update&id=<?php echo $key; ?>&qty=<?php echo $qty + 1; ?>" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">+</a>
                                </div>
                            </div>

                            <div class="col-span-1 md:col-span-2 text-left md:text-right font-bold text-lg text-primary">
                                ₦<?php echo number_format($item_subtotal); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                     <div class="p-6 bg-gray-50 dark:bg-gray-900/50 flex justify-between items-center">
    <a href="products.php" class="group flex items-center gap-2 text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-primary transition-colors uppercase tracking-wide">
        <span class="material-icons-outlined transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:-translate-x-1 group-active:-translate-x-2">arrow_back</span> 
        Continue Shopping
    </a>
</div>
                    </div>
                </div>

                <div class="w-full lg:w-1/3">
                    <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-lg border border-gray-200 dark:border-gray-800 p-8 sticky top-24">
                        <h2 class="text-2xl font-display uppercase mb-6 text-gray-900 dark:text-white">Order Summary</h2>
                        
                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Subtotal</span>
                                <span class="font-medium text-gray-900 dark:text-white">₦<?php echo number_format($subtotal); ?></span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-8">
                            <div class="flex justify-between items-end">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">Order Total</span>
                                <span class="text-3xl font-display text-primary">₦<?php echo number_format($total); ?></span>
                            </div>
                        </div>

                        <a href="checkout.php" class="w-full bg-primary hover:bg-primary-hover text-white font-bold py-4 rounded-lg shadow-lg shadow-primary/30 transition-all transform hover:-translate-y-1 active:translate-y-0 uppercase tracking-widest flex items-center justify-center gap-2">
                            Checkout <span class="material-icons-outlined">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <?php if(!empty($recommendations)): ?>
        <div class="mt-16 lg:mt-24">
            <h3 class="text-2xl font-display uppercase mb-6 text-gray-900 dark:text-white opacity-80">You might also like</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                
                <?php foreach($recommendations as $rec): ?>
                <a href="product_details.php?id=<?php echo $rec['id']; ?>" class="group bg-surface-light dark:bg-surface-dark rounded-xl p-4 border border-gray-200 dark:border-gray-800 hover:border-primary transition-colors cursor-pointer">
                    <div class="aspect-square bg-gray-100 dark:bg-gray-800 rounded-lg mb-4 overflow-hidden relative">
                        <img src="assets/images/<?php echo htmlspecialchars($rec['image']); ?>" alt="<?php echo htmlspecialchars($rec['name']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <?php if($rec['stock_quantity'] < 5): ?>
                            <div class="absolute bottom-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">Low Stock</div>
                        <?php endif; ?>
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($rec['name']); ?></h4>
                    <p class="text-primary font-bold mt-1">₦<?php echo number_format($rec['price']); ?></p>
                </a>
                <?php endforeach; ?>

            </div>
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
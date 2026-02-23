<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db.php'; 

// 1. CALCULATE CART COUNT
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_count = 0;

if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        if (is_array($item) && isset($item['quantity'])) {
            $cart_count += $item['quantity'];
        } elseif (is_numeric($item)) {
            $cart_count += $item;
        }
    }
}

// 2. SEARCH LOGIC & PRODUCT FETCHING
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_results = [];
$featured_products = [];
$best_selling_products = [];

if ($search) {
    // --- SEARCH MODE ---
    $stmt = $pdo->prepare("SELECT p.*, 
        (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating 
        FROM products p 
        WHERE p.name LIKE ? OR p.brand LIKE ? 
        ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%"]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // --- NORMAL MODE (Home Page) ---
    // A. HOT SALES
    $stmt = $pdo->query("SELECT p.*, 
        (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating 
        FROM products p 
        ORDER BY id DESC LIMIT 3"); 
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // B. BEST SELLING
    $stmt = $pdo->query("SELECT p.*, 
        (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating 
        FROM products p 
        ORDER BY RAND() LIMIT 3"); 
    $best_selling_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 3. FETCH ANNOUNCEMENT
$announce = null;
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $announce = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// 4. USER AVATAR LOGIC
$user_picture = $_SESSION['user_picture'] ?? null;
$user_initial = isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JILOMAAC | Original Phones</title>
    
    <link rel="stylesheet" href="assets/css/styled.css?v=2" />
    <link href="https://fonts.googleapis.com/css2?family=Anton&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        corePlugins: {
           preflight: false, 
        },
        theme: {
          extend: {
            colors: {
              primary: "#645bff", 
            },
            boxShadow: {
                'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)'
            }
          },
        },
      };
    </script>
  </head>
  <body>
    <div class="parent" style="display: block; overflow: visible;">
      
      <?php if ($announce && $announce['is_active'] == 1 && !empty($announce['announcement_text'])): ?>
      <div class="announcement-bar">
        🚀 <?php echo htmlspecialchars($announce['announcement_text']); ?>
      </div>
      <?php endif; ?>
      
      <nav id="mainNav">
        <div class="left">
          <div class="tall-text">
            <section class="JIL">JIL</section>
            <div class="highlight"><img src="assets/images/Adobe Express - file.png" alt="" /></div>
            <div><section class="MAAC">MAAC</section></div>
          </div>
        </div>
        <div class="right">
          <h4 class="reflink active"><a href="index.php">Home</a></h4>
          <h4 class="reflink"><a href="accesories.php">Accesories</a></h4>
          <h4 class="reflink"><div class="shop"><a href="products.php">Products</a></div></h4>
          <h4 class="reflink">Deals</h4>
          <h4 class="reflink">About</h4>
        </div>
        
        <div class="rightmost">
            <div id="searchContainer" class="search">
                <div id="searchIcon" class="svgcon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                        <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                    </svg>
                </div>
                <div class="bar">
                    <input type="text" id="searchInput" name="q" placeholder="Search..." autocomplete="off" />
                </div>
                <div id="closeBtn" class="x">X</div>
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
                  <li><a href="index.php" class="mobile-nav-item"><span>Home</span></a></li>
                  <li><a href="products.php" class="mobile-nav-item"><span>Phones</span><span class="material-icons-round mobile-nav-icon">chevron_right</span></a></li>
                  <li><a href="accesories.php" class="mobile-nav-item"><span>Accessories</span><span class="material-icons-round mobile-nav-icon">chevron_right</span></a></li>
                  <li><a href="deals.html" class="mobile-nav-item"><span>Exclusive Deals</span></a></li>
                  <li><a href="about.html" class="mobile-nav-item"><span>Our Story</span></a></li>
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

      <?php if ($search): ?>
          
          <section class="py-20 bg-[#fcfcfc] min-h-screen">
            <div class="max-w-[1400px] mx-auto px-6">
                <div class="flex items-end justify-between mb-12 border-b-2 border-gray-100 pb-8">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-12 h-[3px] bg-[#645bff]"></span>
                            <span class="text-[#645bff] font-extrabold text-sm uppercase tracking-[0.25em]">Results</span>
                        </div>
                        <h3 class="text-5xl font-black text-gray-900 tracking-tighter m-0">
                            Showing results for "<?php echo htmlspecialchars($search); ?>"
                        </h3>
                    </div>
                    <a href="index.php" class="text-base font-bold text-gray-400 hover:text-[#645bff] transition-colors flex items-center gap-2 group">
                        Clear Search <span class="material-icons-round text-lg group-hover:rotate-90 transition-transform">close</span>
                    </a>
                </div>
                
                <?php if (count($search_results) > 0): ?>
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-10">
                        <?php foreach($search_results as $p): 
                            $rating_val = (isset($p['avg_rating']) && $p['avg_rating'] > 0) ? round($p['avg_rating'], 1) : 0;
                            $rating_text = ($rating_val > 0) ? $rating_val : 'New';
                            $star_color = ($rating_val > 0) ? 'text-yellow-400' : 'text-gray-300';
                        ?>
                            <a href="product_details.php?id=<?php echo $p['id']; ?>" class="group bg-white md:bg-[#f8f9fa] rounded-2xl md:rounded-[2.5rem] p-3 md:p-7 pb-4 md:pb-10 shadow-[0_4px_20px_rgba(0,0,0,0.03)] md:shadow-none hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 border border-gray-100 relative flex flex-col h-full text-left no-underline">
                                <div class="aspect-square bg-gray-50 md:bg-white rounded-xl md:rounded-[2rem] flex items-center justify-center mb-3 md:mb-8 relative overflow-hidden">
                                    <img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" class="w-[85%] h-[85%] object-contain mix-blend-multiply transition-transform duration-700 group-hover:scale-110" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                </div>
                                <div class="px-1 md:px-2 flex flex-col flex-grow">
                                    <div class="flex justify-between items-start mb-2 md:mb-4">
                                        <h3 class="text-sm md:text-xl lg:text-2xl font-extrabold text-gray-900 group-hover:text-[#645bff] transition-colors font-sans m-0 leading-tight line-clamp-2 min-h-[2.2rem] md:min-h-[3.6rem]">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </h3>
                                        <div class="flex items-center gap-0.5 md:gap-1 bg-gray-50 md:bg-white px-1.5 md:px-3 py-1 md:py-1.5 rounded-lg md:rounded-xl ml-2 shrink-0 shadow-sm">
                                            <span class="material-icons-round <?php echo $star_color; ?> text-xs md:text-sm">star</span>
                                            <span class="text-xs md:text-sm font-bold text-gray-800"><?php echo $rating_text; ?></span>
                                        </div>
                                    </div>
                                    <p class="text-xs md:text-base text-gray-500 mb-3 md:mb-8 line-clamp-2 flex-grow leading-relaxed hidden sm:block">
                                        <?php echo htmlspecialchars(substr($p['description'], 0, 80)) . '...'; ?>
                                    </p>
                                    <div class="flex items-center justify-between mt-auto pt-2 md:pt-4 border-t md:border-none border-gray-50">
                                        <div class="flex flex-col">
                                            <span class="text-[9px] md:text-xs uppercase tracking-[0.15em] text-gray-400 font-bold mb-0.5">Starting at</span>
                                            <div class="flex items-baseline text-gray-900 leading-none">
                                                <span class="text-lg md:text-3xl font-extrabold mr-0.5 font-sans">₦</span>
                                                <span class="text-xl md:text-3xl lg:text-4xl font-black tracking-tighter font-sans">
                                                    <?php echo number_format($p['price']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="w-8 h-8 md:w-14 md:h-14 rounded-full bg-[#645bff] text-white flex items-center justify-center hover:bg-[#4e44e6] transition-all duration-300 shadow-lg md:shadow-xl shadow-[#645bff]/40 transform active:scale-90">
                                            <span class="material-icons-round text-lg md:text-3xl">add</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-20">
                        <span class="material-icons-round text-6xl text-gray-300 mb-4">search_off</span>
                        <p class="text-gray-400">Try adjusting your search terms or checking for spelling errors.</p>
                    </div>
                <?php endif; ?>
            </div>
          </section>

      <?php else: ?>
          <header class="hero1">
            <div class="hero1-video-container">
              <div class="slide video-slide"></div>
            </div>
            <div class="hero-content">
              <h1 class="main-title">ORIGINAL</h1>
              <div class="title-row"><h2>PH</h2><div class="title-icon"><img src="assets/images/Adobe Express - file.png" alt="O" /></div><h2>NES</h2></div>
              <h1 class="main-title">SMART</h1><h1 class="outline-title">PRICES</h1>
              <div><a href="products.php"> <button class="buttton">Browse</button></a></div>
            </div>
          </header>

          <section><div class="yuu"><h1>WHAT WE SELL</h1></div></section>
          
          <section class="carouselphone">
            <div class="carousel-container">
              <div class="carousel-track">
                 <div class="slide">
                  <picture>
                    <source media="(max-width: 768px)" srcset="assets/images/ng11m.webp" />
                    <img src="assets/images/ng11pc.webp" alt="Tecno Flip" />
                  </picture>
                  <div class="text-overlay"><h2>PHANTOM V Flip2 5G</h2><p>Powered By TECNO AI</p></div>
                </div>
                <div class="slide">
                  <img src="assets/images/x6855_note50pro_homepage_category_pc.jpg" alt="Urban" />
                  <div class="text-overlay"><div class="tite"><img src="assets/images/note-50-pro-wordmark-pc.webp" alt="" /></div><p>All – Round Fast Charge3.0 30W Wireless MagCharge One-Tap Infinix AI</p></div>
                </div>
                <div class="slide"><picture><source media="(max-width: 768px)" srcset="assets/images/750x1088mobile.png" /><img src="assets/images/1920x796.png" alt="Sunset" /></picture></div>
                <div class="slide" style="background-color: white"><picture><source media="(max-width: 768px)" srcset="assets/images/MobileBanner2.jpg" /><img src="assets/images/kv_pc.png" alt="Sunset" /></picture></div>
                <div class="slide"><picture><source media="(max-width: 768px)" srcset="assets/images/750x1088-0409.jpg" /><img src="assets/images/20251223-170420.jpg" alt="Sunset" /></picture></div>
                <div class="slide">
                  <div class="media-wrapper"><img src="assets/images/OBoeb7rlqy.jpg" class="desktop-visual" alt="Sunset" /><video class="mobile-visual" autoplay loop muted playsinline><source src="https://cdn-static.oraimo.com/official/home_m.mp4" type="video/mp4" />Your browser does not support the video tag.</video></div>
                </div>
                <div class="slide"><picture><source media="(max-width: 768px)" srcset="assets/images/OB9csrrf7y.webp" /><img src="assets/images/speaker.jpg" alt="Sunset" /></picture></div>
                <div class="slide"><picture><source media="(max-width: 768px)" srcset="assets/images/OBlqj1gb3f.jpg" /><img src="assets/images/watch tip.jpg" alt="Sunset" /></picture></div>
              </div>
              <button class="nav-btn prev-btn">&#10094;</button><button class="nav-btn next-btn">&#10095;</button><div class="indicators"></div>
            </div>
          </section>

          <section class="trust-badges">
            <div class="badge"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M280-160q-50 0-85-35t-35-85H60l18-80h113q17-19 40-29.5t49-10.5q26 0 49 10.5t40 29.5h167l84-360H182l4-17q6-28 27.5-45.5T264-800h456l-37 160h117l120 160-40 200h-80q0 50-35 85t-85 35q-50 0-85-35t-35-85H400q0 50-35 85t-85 35Zm357-280h193l4-21-74-99h-95l-28 120Zm-19-273 2-7-84 360 2-7 34-146 46-200ZM20-427l20-80h220l-20 80H20Zm80-146 20-80h260l-20 80H100Zm180 333q17 0 28.5-11.5T320-280q0-17-11.5-28.5T280-320q-17 0-28.5 11.5T240-280q0 17 11.5 28.5T280-240Zm400 0q17 0 28.5-11.5T720-280q0-17-11.5-28.5T680-320q-17 0-28.5 11.5T640-280q0 17 11.5 28.5T680-240Z"/></svg><span>Fast Delivery</span></div>
            <div class="badge"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="m344-60-76-128-144-32 14-148-98-112 98-112-14-148 144-32 76-128 136 58 136-58 76 128 144 32-14 148 98 112-98 112 14 148-144 32-76 128-136-58-136 58Zm34-102 102-44 104 44 56-96 110-26-10-112 74-84-74-86 10-112-110-24-58-96-102 44-104-44-56 96-110 24 10 112-74 86 74 84-10 114 110 24 58 96Zm102-318Zm-42 142 226-226-56-58-170 170-86-84-56 56 142 142Z"/></svg><span>1 Year Warranty</span></div>
            <div class="badge"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M160-640h640v-80H160v80Zm-80-80q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v240H160v240h164v80H160q-33 0-56.5-23.5T80-240v-480ZM598-80 428-250l56-56 114 112 226-226 56 58L598-80ZM160-720v480-180 113-413Z"/></svg><span>Secure Payment</span></div>
          </section>

          <section class="py-20 bg-[#fcfcfc]">
              <div class="max-w-[1400px] mx-auto px-6">
                  <div class="flex items-end justify-between mb-12 border-b-2 border-gray-100 pb-8">
                      <div>
                          <div class="flex items-center gap-3 mb-3">
                              <span class="w-12 h-[3px] bg-[#645bff]"></span>
                              <span class="text-[#645bff] font-extrabold text-sm uppercase tracking-[0.25em]">Our Best Offers</span>
                          </div>
                          <h3 id="hotSalesTitle" class="text-5xl font-black text-gray-900 tracking-tighter m-0">Hot Sales</h3>
                      </div>
                      <a href="products.php" class="text-base font-bold text-gray-400 hover:text-[#645bff] transition-colors flex items-center gap-2 group">
                          View all <span class="material-icons-round text-lg group-hover:translate-x-1 transition-transform">arrow_forward</span>
                      </a>
                  </div>
                  
                  <div id="hotSalesGrid" class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-10 transition-opacity duration-300 ease-in-out opacity-100">
                      <?php foreach($featured_products as $p): 
                          $rating_val = (isset($p['avg_rating']) && $p['avg_rating'] > 0) ? round($p['avg_rating'], 1) : 0;
                          $rating_text = ($rating_val > 0) ? $rating_val : 'New';
                          $star_color = ($rating_val > 0) ? 'text-yellow-400' : 'text-gray-300';
                      ?>
                          <a href="product_details.php?id=<?php echo $p['id']; ?>" class="group bg-white md:bg-[#f8f9fa] rounded-2xl md:rounded-[2.5rem] p-3 md:p-7 pb-4 md:pb-10 shadow-[0_4px_20px_rgba(0,0,0,0.03)] md:shadow-none hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 border border-gray-100 relative flex flex-col h-full text-left no-underline">
                              <div class="aspect-square bg-gray-50 md:bg-white rounded-xl md:rounded-[2rem] flex items-center justify-center mb-3 md:mb-8 relative overflow-hidden">
                                  <img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" class="w-[85%] h-[85%] object-contain mix-blend-multiply transition-transform duration-700 group-hover:scale-110" alt="<?php echo htmlspecialchars($p['name']); ?>">
                              </div>
                              <div class="px-1 md:px-2 flex flex-col flex-grow">
                                  <div class="flex justify-between items-start mb-2 md:mb-4">
                                      <h3 class="text-sm md:text-xl lg:text-2xl font-extrabold text-gray-900 group-hover:text-[#645bff] transition-colors font-sans m-0 leading-tight line-clamp-2 min-h-[2.2rem] md:min-h-[3.6rem]">
                                          <?php echo htmlspecialchars($p['name']); ?>
                                      </h3>
                                      <div class="flex items-center gap-0.5 md:gap-1 bg-gray-50 md:bg-white px-1.5 md:px-3 py-1 md:py-1.5 rounded-lg md:rounded-xl ml-2 shrink-0 shadow-sm">
                                          <span class="material-icons-round <?php echo $star_color; ?> text-xs md:text-sm">star</span>
                                          <span class="text-xs md:text-sm font-bold text-gray-800"><?php echo $rating_text; ?></span>
                                      </div>
                                  </div>
                                  <p class="text-xs md:text-base text-gray-500 mb-3 md:mb-8 line-clamp-2 flex-grow leading-relaxed hidden sm:block">
                                      <?php echo htmlspecialchars(substr($p['description'], 0, 80)) . '...'; ?>
                                  </p>
                                  <div class="flex items-center justify-between mt-auto pt-2 md:pt-4 border-t md:border-none border-gray-50">
                                      <div class="flex flex-col">
                                          <span class="text-[9px] md:text-xs uppercase tracking-[0.15em] text-gray-400 font-bold mb-0.5">Starting at</span>
                                          <div class="flex items-baseline text-gray-900 leading-none">
                                              <span class="text-lg md:text-3xl font-extrabold mr-0.5 font-sans">₦</span>
                                              <span class="text-xl md:text-3xl lg:text-4xl font-black tracking-tighter font-sans">
                                                  <?php echo number_format($p['price']); ?>
                                              </span>
                                          </div>
                                      </div>
                                      <div class="w-8 h-8 md:w-14 md:h-14 rounded-full bg-[#645bff] text-white flex items-center justify-center hover:bg-[#4e44e6] transition-all duration-300 shadow-lg md:shadow-xl shadow-[#645bff]/40 transform active:scale-90">
                                          <span class="material-icons-round text-lg md:text-3xl">add</span>
                                      </div>
                                  </div>
                              </div>
                          </a>
                      <?php endforeach; ?>
                  </div>
              </div>
          </section>

          <section class="py-12 px-4">
              <div class="max-w-[1400px] mx-auto">
                  <div class="relative overflow-hidden rounded-[2.5rem] bg-[#111] min-h-[500px] flex items-center border border-white/5 group">
                      <div class="absolute top-[-50%] right-[-10%] w-[800px] h-[800px] bg-[#25D366] opacity-10 blur-[120px] rounded-full pointer-events-none"></div>
                      <div class="absolute bottom-[-50%] left-[-10%] w-[600px] h-[600px] bg-emerald-500 opacity-5 blur-[100px] rounded-full pointer-events-none"></div>

                      <div class="grid lg:grid-cols-2 gap-0 lg:gap-12 items-center relative z-10 w-full p-8 pb-0 lg:p-16 lg:pb-16">
                          <div class="text-left relative z-20 pb-0 lg:pb-0">
                              <div class="inline-flex items-center gap-2 py-1.5 px-4 rounded-full bg-white/5 border border-white/10 text-white text-xs font-bold uppercase tracking-widest mb-6 backdrop-blur-md">
                                  <span class="w-2 h-2 rounded-full bg-[#25D366] animate-pulse"></span>
                                  Jilomaac Swap
                              </div>
                              <h2 class="text-4xl lg:text-7xl font-black text-white leading-[1.05] mb-6 tracking-tight relative z-20">
                                  Your Old Phone <br>
                                  <span class="text-gray-500 text-3xl lg:text-5xl block my-2">+ Token</span>
                                  <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#25D366] to-emerald-400">
                                      = New Device.
                                  </span>
                              </h2>
                              <p class="text-gray-300 text-lg mb-8 max-w-md leading-relaxed font-medium relative z-20">
                                  Want to upgrade? It's simple. 
                                  <span class="text-white font-bold">Send us a message on WhatsApp</span> 
                                  with your current phone's details for an instant valuation.
                              </p>
                              <div class="relative z-30 max-w-[50%] sm:max-w-none">
                                  <a href="https://api.whatsapp.com/send?phone=2349024156052&text=Hello%20Jilomaac,%20I%20want%20to%20swap%20my%20phone" 
                                     target="_blank"
                                     class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#20b859] text-white px-5 py-3.5 rounded-full font-bold transition-all transform hover:-translate-y-1 hover:shadow-lg hover:shadow-emerald-500/30 text-sm sm:text-base w-full sm:w-max justify-center whitespace-nowrap">
                                      <svg class="w-5 h-5 fill-current shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                      Chat to Swap
                                  </a>
                              </div>
                          </div>
                          <div class="relative h-full flex items-start justify-end -mt-14 lg:mt-7 lg:h-auto z-10 pointer-events-cursor">
                              <div class="relative w-[90%] lg:w-full max-w-none ml-auto transform transition-all duration-700 ease-out lg:scale-125 lg:translate-x-10 lg:translate-y-10 group-hover:scale-[1.05] lg:group-hover:scale-130 group-hover:-rotate-2">
                                  <img src="assets/images/potential2.png" class="w-full h-auto object-contain drop-shadow-2xl" alt="Swap Interface Illustration">
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </section>

          <section class="py-20 bg-white">
              <div class="max-w-[1400px] mx-auto px-6">
                  <div class="flex items-end justify-between mb-12 border-b-2 border-gray-100 pb-8">
                      <div>
                          <div class="flex items-center gap-3 mb-3">
                              <span class="w-12 h-[3px] bg-[#645bff]"></span>
                              <span class="text-[#645bff] font-extrabold text-sm uppercase tracking-[0.25em]">Customer Favorites</span>
                          </div>
                          <h3 class="text-5xl font-black text-gray-900 tracking-tighter m-0">Best Selling</h3>
                      </div>
                      <a href="products.php?sort=bestselling" class="text-base font-bold text-gray-400 hover:text-[#645bff] transition-colors flex items-center gap-2 group">
                          View all <span class="material-icons-round text-lg group-hover:translate-x-1 transition-transform">arrow_forward</span>
                      </a>
                  </div>
                  
                  <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-10">
                      <?php foreach($best_selling_products as $p): 
                          $rating_val = (isset($p['avg_rating']) && $p['avg_rating'] > 0) ? round($p['avg_rating'], 1) : 0;
                          $rating_text = ($rating_val > 0) ? $rating_val : 'New';
                          $star_color = ($rating_val > 0) ? 'text-yellow-400' : 'text-gray-300';
                      ?>
                          <a href="product_details.php?id=<?php echo $p['id']; ?>" class="group bg-[#f8f9fa] rounded-2xl md:rounded-[2.5rem] p-3 md:p-7 pb-4 md:pb-10 shadow-[0_4px_20px_rgba(0,0,0,0.03)] md:shadow-none hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 border border-gray-100 relative flex flex-col h-full text-left no-underline">
                              <div class="aspect-square bg-white rounded-xl md:rounded-[2rem] flex items-center justify-center mb-3 md:mb-8 relative overflow-hidden">
                                  <img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" class="w-[85%] h-[85%] object-contain mix-blend-multiply transition-transform duration-700 group-hover:scale-110" alt="<?php echo htmlspecialchars($p['name']); ?>">
                              </div>
                              <div class="px-1 md:px-2 flex flex-col flex-grow">
                                  <div class="flex justify-between items-start mb-2 md:mb-4">
                                      <h3 class="text-sm md:text-xl lg:text-2xl font-extrabold text-gray-900 group-hover:text-[#645bff] transition-colors font-sans m-0 leading-tight line-clamp-2 min-h-[2.2rem] md:min-h-[3.6rem]">
                                          <?php echo htmlspecialchars($p['name']); ?>
                                      </h3>
                                      <div class="flex items-center gap-0.5 md:gap-1 bg-white px-1.5 md:px-3 py-1 md:py-1.5 rounded-lg md:rounded-xl ml-2 shrink-0 shadow-sm">
                                          <span class="material-icons-round <?php echo $star_color; ?> text-xs md:text-sm">star</span>
                                          <span class="text-xs md:text-sm font-bold text-gray-800"><?php echo $rating_text; ?></span>
                                      </div>
                                  </div>
                                  <p class="text-xs md:text-base text-gray-500 mb-3 md:mb-8 line-clamp-2 flex-grow leading-relaxed hidden sm:block">
                                      <?php echo htmlspecialchars(substr($p['description'], 0, 80)) . '...'; ?>
                                  </p>
                                  <div class="flex items-center justify-between mt-auto pt-2 md:pt-4 border-t md:border-none border-gray-50">
                                      <div class="flex flex-col">
                                          <span class="text-[9px] md:text-xs uppercase tracking-[0.15em] text-gray-400 font-bold mb-0.5">Starting at</span>
                                          <div class="flex items-baseline text-gray-900 leading-none">
                                              <span class="text-lg md:text-3xl font-extrabold mr-0.5 font-sans">₦</span>
                                              <span class="text-xl md:text-3xl lg:text-4xl font-black tracking-tighter font-sans">
                                                  <?php echo number_format($p['price']); ?>
                                              </span>
                                          </div>
                                      </div>
                                      <div class="w-8 h-8 md:w-14 md:h-14 rounded-full bg-[#645bff] text-white flex items-center justify-center hover:bg-[#4e44e6] transition-all duration-300 shadow-lg md:shadow-xl shadow-[#645bff]/40 transform active:scale-90">
                                          <span class="material-icons-round text-lg md:text-3xl">add</span>
                                      </div>
                                  </div>
                              </div>
                          </a>
                      <?php endforeach; ?>
                  </div>
              </div>
          </section>

      <?php endif; ?>

      <footer>
        <div class="footer-content">
          <div class="footer-section"><h3>JILOMAAC</h3><p>Original Phones. Smart Prices.</p></div>
          <div class="footer-section"><h4>Quick Links</h4><a href="index.php">Home</a><a href="products.php">Phones</a><a href="#">Warranty Policy</a></div>
          <div class="footer-section"><h4>Stay Updated</h4><input type="email" placeholder="Enter your email" /><button>Subscribe</button></div>
        </div>
        <div class="footer-bottom">&copy; 2024 JILOMAAC. All rights reserved.</div>
      </footer>
    </div>
    
    <script src="assets/js/script.js"></script>

  </body>
</html>
<?php
session_start();
require_once 'db.php'; // Needed for announcement bar and products

// ==============================================================
// 1. AJAX ADD-TO-CART LISTENER (Runs in background)
// ==============================================================
if (isset($_GET['ajax_add']) && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    // Fetch the product from DB
    $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        // Initialize cart if needed
        if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

        // Use 'p_' prefix for simple products (no variants)
        $cart_key = "p_" . $product_id;

        // Add or update quantity
        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$cart_key] = [
                'product_id' => $prod['id'],
                'name'       => $prod['name'],
                'price'      => $prod['price'],
                'image'      => $prod['image'],
                'quantity'   => 1,
                'options'    => [] // No variants for accessories
            ];
        }

        // Calculate new total cart count to send back to JS
        $new_count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $new_count += $item['quantity'];
        }

        // Return success response to JavaScript
        echo json_encode(['success' => true, 'new_count' => $new_count]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit; // Stop running the rest of the page
}
// ==============================================================


// 2. CALCULATE CART COUNT FOR PAGE LOAD
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $qty = is_array($item) ? ($item['quantity'] ?? 1) : $item;
        $cart_count += $qty;
    }
}

// 3. FETCH ANNOUNCEMENT BAR
$announce = null;
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $announce = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// 4. FETCH ACCESSORIES FOR THE GRID
$sql = "SELECT p.*, 
        (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
        FROM products p 
        WHERE category = 'Accessories' AND status = 'active'
        ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$accessories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. FETCH CAROUSEL SLIDES (WITH JOIN & ANTI-CRASH)
$carousel_slides = [];
if (isset($pdo)) {
    try {
        $sql_carousel = "SELECT c.*, p.name as real_product_name 
                         FROM carousel_slides c 
                         LEFT JOIN products p ON c.product_id = p.id 
                         ORDER BY c.id ASC";
        $stmt = $pdo->query($sql_carousel);
        $carousel_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $slide_count = count($carousel_slides);
        if ($slide_count == 1) {
            $carousel_slides = array_merge($carousel_slides, $carousel_slides, $carousel_slides);
        } elseif ($slide_count == 2) {
            $carousel_slides = array_merge($carousel_slides, $carousel_slides);
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Accessories - JILOMAAC</title>
  
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

  <link rel="stylesheet" href="assets/css/styled.css">
  
  <style>
      #toast {
          visibility: hidden;
          min-width: 250px;
          background-color: #645bff;
          color: white;
          text-align: center;
          border-radius: 8px;
          padding: 16px;
          position: fixed;
          z-index: 99999;
          right: 30px;
          top: 30px;
          font-family: 'Plus Jakarta Sans', sans-serif;
          font-weight: 600;
          box-shadow: 0 4px 15px rgba(100, 91, 255, 0.4);
          display: flex;
          align-items: center;
          gap: 10px;
          transform: translateY(-50px);
          opacity: 0;
          transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      }
      #toast.show {
          visibility: visible;
          transform: translateY(0);
          opacity: 1;
      }
  </style>
</head>

<body data-category="Accessories">

 <div id="toast">
    <span class="material-icons-round">check_circle</span>
    Item successfully added to cart!
 </div>

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
            <div class="highlights"><img src="assets/images/Adobe Express - file.png" alt="" /></div>
            <div><section class="MAAC">MAAC</section></div>
          </div>
        </div>
        <div class="right">
          <h4 class="reflink "><a href="index.php">Home</a></h4>
          <h4 class="reflink active"><a href="accesories.php">Accesories</a></h4>
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
                <span id="cart-badge-icon" class="cart-badge" style="<?php echo ($cart_count > 0) ? 'display:flex;' : 'display:none;'; ?>">
                    <?php echo $cart_count; ?>
                </span>
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
<div class="carousel">
  <div class="list">
    
    <?php if(!empty($carousel_slides)): ?>
        <?php foreach($carousel_slides as $slide): ?>
        <div class="item">
          <img src="assets/images/<?php echo htmlspecialchars($slide['image']); ?>">
          
          <div class="intro">
            <div class="title"><?php echo htmlspecialchars($slide['title']); ?></div>
            <div class="topic"><?php echo htmlspecialchars($slide['topic']); ?></div>
            <div class="des"><?php echo htmlspecialchars($slide['short_desc']); ?></div>
            <button class="seeMore">See More &#8599;</button>
          </div>
          
          <div class="detail">
            <div class="title"><?php echo htmlspecialchars($slide['real_product_name'] ?? 'Product Unlinked!'); ?></div>
            <div class="des"><?php echo htmlspecialchars($slide['long_desc']); ?></div>
            
            <div class="specifications">
              <div><p>Used time</p><p><?php echo htmlspecialchars($slide['spec_time']); ?></p></div>
              <div><p>Charging port</p><p><?php echo htmlspecialchars($slide['spec_port']); ?></p></div>
              <div><p>Compatible</p><p><?php echo htmlspecialchars($slide['spec_os']); ?></p></div>
              <div><p>Bluetooth</p><p><?php echo htmlspecialchars($slide['spec_bt']); ?></p></div>
              <div><p>Controlled</p><p><?php echo htmlspecialchars($slide['spec_control']); ?></p></div>
            </div>
            
            <div class="checkout" style="position: relative; z-index: 9999; pointer-events: auto;">
              <button onclick="addToCartAjax(<?php echo $slide['product_id']; ?>)">ADD TO CART</button>
             <button onclick="window.location.href='cart.php'">GO TO CHECKOUT</button>
            </div>

          </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="item">
            <img src="assets/images/placeholder.jpg">
            <div class="intro"><div class="title">NO SLIDES FOUND</div></div>
        </div>
    <?php endif; ?>

  </div>

  <div class="arrows">
    <button id="prev"><</button>
    <button id="back">Go back &#8599;</button>
    <button id="next">></button>
  </div>
</div>
</div> 

<main class="max-w-[1400px] mx-auto px-6 py-20 w-full bg-white dark:bg-[#121212]">
    
    <header class="mb-12 border-b-2 border-gray-100 dark:border-gray-800 pb-8">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-12 h-[3px] bg-[#645bff]"></span>
            <span class="text-[#645bff] font-extrabold text-sm uppercase tracking-[0.25em]">Enhance Your Experience</span>
        </div>
        <h1 id="featuredTitle" class="text-5xl font-black text-gray-900 dark:text-white tracking-tighter m-0">
            Smart Accessories
        </h1>
    </header>

    <?php if(empty($accessories)): ?>
        <div class="text-center py-20 bg-white dark:bg-card-dark rounded-[2.5rem] border border-gray-100">
            <span class="material-icons-round text-7xl text-gray-200 mb-6">headset_off</span>
            <p class="text-gray-500">Check back later for new stock.</p>
        </div>
    <?php else: ?>
        
        <div id="productGrid" class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-10">
            
            <?php foreach($accessories as $p): ?>
                <?php
                    $rating_val = isset($p['avg_rating']) && $p['avg_rating'] > 0 ? round($p['avg_rating'], 1) : 0;
                    $rating_text = $rating_val > 0 ? $rating_val : 'New';
                    $rating_color = $rating_val > 0 ? 'text-yellow-400' : 'text-gray-300';
                    $is_hot = ($rating_val >= 4.5); 
                ?>
                <a href="product_details.php?id=<?php echo $p['id']; ?>" class="group bg-white dark:bg-card-dark rounded-2xl md:rounded-[2.5rem] p-3 md:p-7 pb-4 md:pb-10 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 border border-transparent hover:border-gray-100 dark:hover:border-gray-700 relative flex flex-col h-full text-left no-underline overflow-hidden">
                    
                    <div class="aspect-square bg-gray-50 dark:bg-gray-800 rounded-xl md:rounded-[2rem] flex items-center justify-center mb-3 md:mb-8 relative overflow-hidden">
                        
                        <?php if($p['stock_quantity'] < 5 && $p['stock_quantity'] > 0): ?>
                            <span class="absolute top-2 right-2 md:top-5 md:right-5 bg-red-500 text-white text-[8px] md:text-[10px] uppercase font-black px-2 py-1 md:px-3 md:py-1.5 rounded-full z-10 tracking-widest">Low Stock</span>
                        <?php elseif($is_hot): ?>
                            <span class="absolute top-2 right-2 md:top-5 md:right-5 bg-orange-500 text-white text-[8px] md:text-[10px] uppercase font-black px-2 py-1 md:px-3 md:py-1.5 rounded-full z-10 tracking-widest flex items-center gap-1">
                                <span class="material-icons-round text-[10px] md:text-[12px]">local_fire_department</span> Hot
                            </span>
                        <?php endif; ?>
                        
                        <img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" class="w-[85%] h-[85%] object-contain mix-blend-multiply dark:mix-blend-normal transition-transform duration-700 group-hover:scale-110" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    </div>

                    <div class="px-1 md:px-2 flex flex-col flex-grow">
                        <div class="flex justify-between items-start mb-2 md:mb-4">
                            <h3 class="text-sm md:text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white group-hover:text-[#645bff] transition-colors font-sans m-0 leading-tight line-clamp-2 min-h-[2.2rem] md:min-h-[3.6rem]">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </h3>
                            
                            <div class="flex items-center gap-0.5 md:gap-1 bg-gray-50 dark:bg-card-dark px-1.5 md:px-3 py-1 md:py-1.5 rounded-lg md:rounded-xl ml-2 shrink-0">
                                <span class="material-icons-round <?php echo $rating_color; ?> text-xs md:text-sm">star</span>
                                <span class="text-xs md:text-sm font-bold text-gray-800 dark:text-gray-200"><?php echo $rating_text; ?></span>
                            </div>
                        </div>
                        
                        <p class="text-xs md:text-base text-gray-500 dark:text-gray-400 mb-3 md:mb-8 line-clamp-2 flex-grow leading-relaxed hidden sm:block">
                            <?php echo htmlspecialchars(substr($p['description'], 0, 80)) . '...'; ?>
                        </p>

                        <div class="flex items-center justify-between mt-auto pt-2 md:pt-4 border-t md:border-none border-gray-50">
                            <div class="flex flex-col">
                                <span class="text-[9px] md:text-xs uppercase tracking-[0.15em] text-gray-400 font-bold mb-0.5">Price</span>
                                <div class="flex items-baseline text-gray-900 dark:text-white leading-none">
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
    <?php endif; ?>
</main>

  <script src="assets/js/script.js"></script>
  
  <script>
      function addToCartAjax(productId) {
          // 1. Send request silently to the PHP listener at the top of this very file
          fetch(`accesories.php?ajax_add=true&id=${productId}`)
          .then(response => response.json())
          .then(data => {
              if(data.success) {
                  // 2. Show the Success Toast Animation
                  let toast = document.getElementById("toast");
                  toast.className = "show";
                  setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 2500);

                  // 3. Update the Cart Badge safely
                  let badge = document.getElementById('cart-badge-icon');
                  badge.innerText = data.new_count;
                  badge.style.display = 'flex'; 
              }
          })
          .catch(error => console.error('Error:', error));
      }
  </script>
</body>
</html>
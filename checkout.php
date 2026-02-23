<?php
session_start();

// 1. SECURITY: If cart is empty, kick them back to shop
if (empty($_SESSION['cart'])) {
    header("Location: products.php");
    exit;
}

// 2. LOGIC: Initialize Variables
$cart_items = $_SESSION['cart'];
$subtotal = 0;

// 3. LOGIC: Calculate Subtotal
foreach($cart_items as $item) {
    if(is_array($item)) {
        $qty = $item['quantity'] ?? 1;
        $subtotal += ($item['price'] * $qty);
    } elseif(is_numeric($item)) {
        $subtotal += $item;
    }
}

ini_set('display_errors', 1);
error_reporting(E_ALL);


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
    // Fetch products that match the search term
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - JILOMAAC</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        corePlugins: { preflight: false }, // This protects your Nav styling
        theme: {
          extend: {
            colors: {
              primary: "#645bff", // Jilomaac Green
              "bg-light": "#f8f9fa",
              "surface-white": "#ffffff",
              "border-gray": "#e5e7eb",
              "text-dark": "#1f2937",
              "text-gray": "#6b7280",
            },
            fontFamily: { sans: ["Poppins", "sans-serif"] },
            boxShadow: { 'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)' }
          },
        },
      };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>

    <style>
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        
        /* Your Custom styles for inputs to ensure they look good with Tailwind disabled */
        .custom-input {
            width: 100%; padding: 12px 16px; 
            border: 1px solid #e5e7eb; border-radius: 0.5rem; 
            background: #fff; color: #1f2937;
            transition: all 0.2s;
        }
        .custom-input:focus {
            outline: none; border-color: #22C55E; 
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
    </style>
    <link rel="stylesheet" href="assets/css/styled.css" />
</head>
<body>

      <nav id="mainNav">
        <div class="left">
          <div class="tall-text">
            <section class="JIL">JIL</section>
            <div class="highlight"><img src="assets/images/Adobe Express - file.png" alt="" /></div>
            <div><section class="MAAC">MAAC</section></div>
          </div>
        </div>
        
        
<div class="rightmost">
 <a href="cart.php" class="back-cart-btn">
    <span class="material-icons-outlined arrow-icon">arrow_back</span>
    <span class="btn-text">Back to Cart</span>
</a>
</div>
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
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-7">
                <div class="bg-white p-8 rounded-2xl shadow-soft border border-gray-100">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                        <span class="material-icons-outlined text-primary">local_shipping</span>
                        Shipping Details
                    </h2>
                    
                    <form action="place_order.php" method="POST" id="checkoutForm" class="space-y-5">
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="customer_name" class="custom-input" placeholder="John Doe" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" name="customer_phone" class="custom-input" placeholder="080..." required>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                                <input type="email" name="customer_email" class="custom-input" placeholder="john@example.com" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Delivery Address</label>
                            <textarea name="customer_address" rows="3" class="custom-input" placeholder="Street address, Apartment, etc." required></textarea>
                        </div>

                        <input type="hidden" name="total_amount" id="form_total_amount" value="<?php echo $subtotal; ?>">
                        <input type="hidden" name="payment_reference" id="payment_reference">
                        <input type="hidden" id="js_amount" value="<?php echo $subtotal * 100; ?>">
                        <input type="hidden" name="shipping_cost" id="hidden_shipping_cost" value="0">

                    </form>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100 sticky top-4">
                    <h2 class="text-xl font-bold mb-6 text-gray-800 border-b border-gray-100 pb-4">Order Summary</h2>
                    
                    <div class="space-y-4 mb-6 max-h-64 overflow-y-auto custom-scrollbar">
                        <?php foreach($cart_items as $item): if(is_array($item)): ?>
                            <div class="flex justify-between items-center py-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center border border-gray-200">
                                        <?php if(isset($item['image'])): ?>
                                            <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" class="w-full h-full object-contain">
                                        <?php else: ?>
                                            <span class="material-icons-outlined text-gray-400 text-sm">smartphone</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-sm text-gray-800 line-clamp-1"><?php echo htmlspecialchars($item['name']); ?></p>
                                        <p class="text-xs text-gray-500">Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                </div>
                                <span class="font-semibold text-gray-900 text-sm">₦<?php echo number_format($item['price'] * $item['quantity']); ?></span>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl mb-6 border border-gray-100">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Shipping Destination</label>
                        <div class="flex gap-2">
                            <select id="state_selector" class="custom-input text-sm py-2">
                                <option value="" selected>Select State</option>
                                <option value="Lagos">Lagos</option>
                                <option value="Ogun">Ogun</option>
                                <option value="Oyo">Oyo</option>
                                <option value="Abuja">Abuja</option>
                                <option value="Rivers">Rivers</option>
                                <option value="Kano">Kano</option>
                            </select>
                            <button onclick="calculateShipping()" id="calc_btn" class="bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors">
                                Check
                            </button>
                        </div>
                        <div id="shipping_msg" class="text-xs text-gray-400 mt-2 h-4"></div>
                    </div>

                    <div class="space-y-3 border-t border-gray-100 pt-4">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-bold text-gray-900" id="display_subtotal" data-amount="<?php echo $subtotal; ?>">₦<?php echo number_format($subtotal); ?></span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Delivery Fee</span>
                            <span class="font-bold text-gray-900" id="display_shipping">₦0.00</span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-gray-100 mt-4">
                            <span class="text-lg font-bold text-gray-800">Total</span>
                            <span class="text-2xl font-bold text-primary" id="display_total">₦<?php echo number_format($subtotal); ?></span>
                        </div>
                    </div>

                    <button onclick="payWithPaystack()" id="pay_btn" class="w-full bg-primary hover:bg-green-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-green-100 mt-8 transition-all opacity-50 cursor-not-allowed flex justify-center items-center gap-2 pointer-events-none">
                        <span id="pay_btn_text">Select State First</span>
                        <span class="material-icons-outlined">arrow_forward</span>
                    </button>
                    
                    <div class="text-center mt-4 flex items-center justify-center gap-1 text-xs text-gray-400">
                        <span class="material-icons-outlined text-sm">lock</span> Secured by Paystack
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="https://js.paystack.co/v1/inline.js"></script>
<script src="assets/js/script.js"></script>
    <script>
        function calculateShipping() {
            const state = document.getElementById('state_selector').value;
            const btn = document.getElementById('calc_btn');
            const msg = document.getElementById('shipping_msg');

            if(state === "") { alert("Please select a state."); return; }

            btn.innerText = "...";
            msg.innerText = "Fetching rate...";
            msg.className = "text-xs text-blue-500";

            fetch('get_shipping.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ state: state })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    const shippingCost = data.data.amount;
                    document.getElementById('display_shipping').innerText = "₦" + shippingCost.toLocaleString();
                    document.getElementById('hidden_shipping_cost').value = shippingCost;
                    
                    updateGrandTotal(shippingCost);

                    btn.innerText = "✓";
                    msg.innerText = "Verified via " + data.data.carrier;
                    msg.className = "text-xs text-green-600 font-bold";
                    
                    const payBtn = document.getElementById('pay_btn');
                    payBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
                    document.getElementById('pay_btn_text').innerText = "PAY NOW";
                } else {
                    msg.innerText = "Could not fetch rate.";
                    msg.className = "text-xs text-red-500";
                    btn.innerText = "Retry";
                }
            })
            .catch(error => {
                console.error(error);
                msg.innerText = "Network Error.";
                msg.className = "text-xs text-red-500";
                btn.innerText = "Retry";
            });
        }

        function updateGrandTotal(shippingCost) {
            let subtotal = parseFloat(document.getElementById('display_subtotal').dataset.amount);
            let total = subtotal + parseFloat(shippingCost);
            document.getElementById('display_total').innerText = "₦" + total.toLocaleString();
            document.getElementById('js_amount').value = total * 100;
            document.getElementById('form_total_amount').value = total;
        }

        function payWithPaystack() {
            const emailInput = document.querySelector('input[name="customer_email"]');
            if(emailInput.value === "") { alert("Enter email address!"); emailInput.focus(); return; }

            const amountToCharge = document.getElementById('js_amount').value;
            const form = document.getElementById('checkoutForm');

            let handler = PaystackPop.setup({
                key: 'pk_test_45575b1e571b63daef5f23bc98bd6191de05d7b2',
                email: emailInput.value,
                amount: amountToCharge,
                currency: "NGN",
                ref: ''+Math.floor((Math.random() * 1000000000) + 1),
                callback: function(response) {
                    document.getElementById('payment_reference').value = response.reference;
                    form.submit();
                },
                onClose: function() { alert('Transaction cancelled.'); }
            });
            handler.openIframe();
        }
    </script>

</body>
</html>
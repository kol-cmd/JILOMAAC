<?php
require_once 'db.php';

// 1. CAPTURE ALL FILTERS FROM JAVASCRIPT
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// 2. BASE SQL QUERY
$sql = "SELECT p.*, 
        (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
        FROM products p 
        WHERE (p.name LIKE ? OR p.description LIKE ?) 
        AND p.status = 'active'";

$params = ["%$search%", "%$search%"];

// 3. APPLY BRAND & CATEGORY FILTERS
if (!empty($category)) {
    $sql .= " AND p.category = ?";
    $params[] = $category;
}

if (!empty($brand)) {
    // If a brand is selected, STRICTLY search within that brand
    $sql .= " AND p.name LIKE ?"; 
    $params[] = "%$brand%";
}

// 4. APPLY SORTING ORDER
switch ($sort) {
    case 'price_asc': $sql .= " ORDER BY p.price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY p.price DESC"; break;
    case 'hot': $sql .= " ORDER BY avg_rating DESC, p.id DESC"; break;
    case 'bestselling': $sql .= " ORDER BY review_count DESC, p.id DESC"; break;
    case 'newest': default: $sql .= " ORDER BY p.id DESC"; break;
}

// 5. EXECUTE QUERY
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. OUTPUT THE HTML RESULTS (Using your exact Tailwind HTML)
if (count($products) > 0) {
    foreach ($products as $p) {
        $rating_val = isset($p['avg_rating']) && $p['avg_rating'] > 0 ? round($p['avg_rating'], 1) : 0;
        $rating_text = $rating_val > 0 ? $rating_val : 'New';
        $rating_color = $rating_val > 0 ? 'text-yellow-400' : 'text-gray-300';
        $is_hot = ($rating_val >= 4.5); 
        ?>
        <a href="product_details.php?id=<?php echo $p['id']; ?>" class="group bg-white dark:bg-card-dark rounded-[2.5rem] p-7 pb-10 shadow-[0_10px_40px_rgba(0,0,0,0.04)] hover:shadow-2xl hover:-translate-y-3 transition-all duration-500 border border-transparent hover:border-gray-100 dark:hover:border-gray-700 relative flex flex-col h-full text-left no-underline overflow-hidden">
            
            <div class="aspect-square bg-gray-50 dark:bg-subtle-dark rounded-[2rem] flex items-center justify-center mb-8 relative overflow-hidden">
                <?php if($p['stock_quantity'] < 5 && $p['stock_quantity'] > 0): ?>
                    <span class="absolute top-5 right-5 bg-secondary text-white text-[10px] uppercase font-black px-3 py-1.5 rounded-full z-10 tracking-widest">Low Stock</span>
                <?php elseif($is_hot): ?>
                    <span class="absolute top-5 right-5 bg-orange-500 text-white text-[10px] uppercase font-black px-3 py-1.5 rounded-full z-10 tracking-widest flex items-center gap-1">
                        <span class="material-icons-round text-[12px]">local_fire_department</span> Hot
                    </span>
                <?php endif; ?>
                
                <img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" class="w-[85%] h-[85%] object-contain mix-blend-multiply dark:mix-blend-normal filter dark:brightness-90 transition-transform duration-700 group-hover:scale-110" alt="<?php echo htmlspecialchars($p['name']); ?>">
            </div>

            <div class="px-2 flex flex-col flex-grow">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-2xl md:text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white group-hover:text-primary transition-colors font-sans m-0 leading-tight line-clamp-2 min-h-[3.6rem]">
                        <?php echo htmlspecialchars($p['name']); ?>
                    </h3>
                    
                    <div class="flex items-center gap-1 bg-gray-50 dark:bg-subtle-dark px-3 py-1.5 rounded-xl ml-3 shrink-0">
                        <span class="material-icons-round <?php echo $rating_color; ?> text-sm">star</span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200"><?php echo $rating_text; ?></span>
                    </div>
                </div>
                
                <p class="text-base text-gray-500 dark:text-gray-400 mb-8 line-clamp-2 flex-grow leading-relaxed">
                    <?php echo htmlspecialchars(substr($p['description'], 0, 80)) . '...'; ?>
                </p>

                <div class="flex items-center justify-between mt-auto pt-4">
                    <div class="flex flex-col">
                        <span class="text-xs uppercase tracking-[0.15em] text-gray-400 font-bold mb-1">Starting at</span>
                        
                        <div class="flex items-baseline text-gray-900 dark:text-white leading-none">
                            <span class="text-3xl font-extrabold mr-0.5 font-sans">₦</span>
                            <span class="text-4xl lg:text-3xl xl:text-4xl font-black tracking-tighter font-sans">
                                <?php echo number_format($p['price']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-primary text-white flex items-center justify-center hover:bg-[#4e44e6] transition-all duration-300 shadow-xl shadow-primary/40 transform active:scale-90">
                        <span class="material-icons-round text-3xl">add</span>
                    </div>
                </div>
            </div>
        </a>
        <?php
    }
} else {
    // WHAT TO SHOW IF NO PRODUCTS ARE FOUND
    echo '<div class="col-span-full text-center py-20 bg-white dark:bg-card-dark rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
            <span class="material-icons-round text-7xl text-gray-200 dark:text-gray-700 mb-6">search_off</span>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No products found for "'.htmlspecialchars($search).'"</h3>
            <p class="text-gray-500">Try checking your spelling or searching for a different term.</p>
          </div>';
}
?>
<?php
// get_shipping.php
header('Content-Type: application/json');

// 1. Get the data from the Javascript fetch() call
$input = json_decode(file_get_contents('php://input'), true);
$state = $input['state'] ?? '';
$city  = $input['city'] ?? '';

// 2. Define your Logistics API Keys (You get these from GIG or Terminal Africa)
// For now, I have put placeholders.
$api_key = "YOUR_LIVE_SECRET_KEY"; 
$api_url = "https://api.terminal.africa/v1/rates/shipment"; // Example endpoint

if(empty($state)) {
    echo json_encode(['success' => false, 'message' => 'State is required']);
    exit;
}

// 3. THE PROFESSIONAL "cURL" REQUEST
// This is how PHP talks to other servers.
try {
    
    // --- SIMULATION BLOCK (DELETE THIS WHEN YOU HAVE REAL KEYS) ---
    // Since you don't have keys yet, we simulate the API response so your site works NOW.
    // This logic mimics exactly what a real API returns.
    $mock_rates = [
        'Lagos' => 3500,
        'Ogun' => 5500,
        'Oyo' => 6000,
        'Abuja' => 9000,
        'Rivers' => 9500,
        'Kano' => 12000
    ];
    
    // Default to 15,000 if state not found in our mock list
    $price = $mock_rates[$state] ?? 15000; 
    
    // Simulate network delay (Real APIs take 1-2 seconds)
    sleep(1); 
    
    echo json_encode([
        'success' => true,
        'data' => [
            'amount' => $price,
            'carrier' => 'GIG Logistics' // or 'FedEx'
        ]
    ]);
    exit;
    // --- END SIMULATION BLOCK ---


    // 4. THE REAL CODE (Uncomment this when you get your Keys)
    /*
    $payload = [
        'pickup_address' => ['state' => 'Lagos', 'city' => 'Ikeja'],
        'delivery_address' => ['state' => $state, 'city' => $city],
        'parcel' => ['weight' => 2, 'items' => 1] // Assuming 2kg package
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    echo $response; // Send the real JSON back to checkout
    */

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'API Error']);
}
?>
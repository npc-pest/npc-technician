<?php
// Test the final endpoint directly

header('Content-Type: text/plain; charset=utf-8');

echo "=== Testing Final Endpoint ===\n\n";

// Test data
$testData = [
    'booking_id' => 'BK202508123634',
    'status' => 'completed',
    'notes' => 'Test completion'
];

$testHeaders = [
    'Technician-ID: c0e24a60702832b71e9678ae6d836d74143133e03c8a39c444e13b5e9046c877'
];

echo "Step 1: Testing with cURL...\n";
echo "===========================\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://npcpest.com/npc/backend/technician_status_update_final.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Technician-ID: c0e24a60702832b71e9678ae6d836d74143133e03c8a39c444e13b5e9046c877'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";
echo "Response:\n$response\n\n";

echo "Step 2: Testing with file_get_contents...\n";
echo "=========================================\n";

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Technician-ID: c0e24a60702832b71e9678ae6d836d74143133e03c8a39c444e13b5e9046c877'
        ],
        'content' => json_encode($testData)
    ]
]);

$url = 'https://npcpest.com/npc/backend/technician_status_update_final.php';
$result = file_get_contents($url, false, $context);

if ($result === false) {
    echo "❌ Failed to get response\n";
} else {
    echo "✅ Response received:\n$result\n";
}

echo "\n=== Test Complete ===\n";
?>

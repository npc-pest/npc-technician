<?php
// tamil_nadu_locations.php
// Provides comprehensive list of Tamil Nadu cities and districts

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Tamil Nadu Districts and Major Cities
$tamilNaduLocations = [
    // Major Cities (Metropolitan and Municipal Corporations)
    'Chennai',
    'Coimbatore', 
    'Madurai',
    'Salem',
    'Tiruchirappalli',
    'Vellore',
    'Erode',
    'Tiruppur',
    'Thoothukkudi',
    'Dindigul',
    'Thanjavur',
    'Ranipet',
    'Sivakasi',
    'Karur',
    'Udhagamandalam (Ooty)',
    'Hosur',
    'Nagercoil',
    'Kanchipuram',
    'Kumbakonam',
    'Cuddalore',
    'Tirunelveli',
    'Pollachi',
    'Rajapalayam',
    'Sivaganga',
    'Pudukkottai',
    'Vaniyambadi',
    'Ambur',
    'Nagapattinam',
    'Gudiyatham',
    'Paramakudi',
    'Arakkonam',
    'Tiruvannamalai',
    'Panruti',
    'Arcot',
    'Virudhunagar',
    'Sankarankovil',
    'Tenkasi',
    'Palani',
    'Pattukkottai',
    'Tiruchengode',
    'Periyakulam',
    'Usilampatti',
    'Vedaranyam',
    'Sathyamangalam',
    'Puliyankudi',
    'Dharapuram',
    'Kadayanallur',
    'Valparai',
    'Tirupathur',
    'Vellakoil',
    'Ambasamudram',
    'Tirumangalam',
    'Krishnagiri',
    'Mettupalayam',
    'Gobichettipalayam',
    'Perambalur',
    'Manapparai',
    'Aruppukkottai',
    'Sivagiri',
    'Tindivanam',
    'Katpadi',
    'Uthamapalayam',
    'Palayamkottai',
    'Karaikudi',
    'Vandavasi',
    'Tirukkoyilur',
    'Ambasamudram',
    'Mannargudi',
    'Thiruthuraipoondi',
    'Palladam',
    'Kumbakonam',
    'Mayiladuthurai',
    'Tiruvallur',
    'Chengalpattu',
    'Villupuram',
    'Kallakurichi',
    'Namakkal',
    'Perambalur',
    'Pudukkottai',
    'Sivaganga',
    'Ramanathapuram',
    'Virudhunagar',
    'Theni',
    'Dindigul',
    'Karur',
    'Tiruchirappalli',
    'Perambalur',
    'Ariyalur',
    'Cuddalore',
    'Nagapattinam',
    'Thiruvarur',
    'Thanjavur',
    'Pudukkottai',
    'Sivaganga',
    'Madurai',
    'Theni',
    'Virudhunagar',
    'Ramanathapuram',
    'Thoothukkudi',
    'Tirunelveli',
    'Tenkasi',
    'Kanniyakumari',
    'Coimbatore',
    'Tiruppur',
    'Erode',
    'Salem',
    'Namakkal',
    'Dharmapuri',
    'Krishnagiri',
    'Vellore',
    'Tirupathur',
    'Ranipet',
    'Kanchipuram',
    'Chengalpattu',
    'Tiruvallur',
    'Chennai',
    'Villupuram',
    'Kallakurichi',
    'Cuddalore',
    'Tiruvannamalai',
    'Vellore',
    'Krishnagiri',
    'Dharmapuri',
    'Salem',
    'Namakkal',
    'Erode',
    'Coimbatore',
    'Tiruppur',
    'Nilgiris',
    'Tiruchirappalli',
    'Perambalur',
    'Ariyalur',
    'Cuddalore',
    'Nagapattinam',
    'Thiruvarur',
    'Thanjavur',
    'Pudukkottai',
    'Sivaganga',
    'Madurai',
    'Theni',
    'Virudhunagar',
    'Ramanathapuram',
    'Thoothukkudi',
    'Tirunelveli',
    'Tenkasi',
    'Kanniyakumari'
];

// Remove duplicates and sort
$tamilNaduLocations = array_unique($tamilNaduLocations);
sort($tamilNaduLocations);

// Get search query if provided
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($searchQuery)) {
        // Filter locations based on search query
        $filteredLocations = array_filter($tamilNaduLocations, function($location) use ($searchQuery) {
            return stripos($location, $searchQuery) !== false;
        });
        
        // Limit results to 10 for better performance
        $filteredLocations = array_slice(array_values($filteredLocations), 0, 10);
        
        echo json_encode([
            'status' => 'success',
            'locations' => $filteredLocations,
            'total_count' => count($filteredLocations),
            'search_query' => $searchQuery
        ]);
    } else {
        // Return all locations
        echo json_encode([
            'status' => 'success',
            'locations' => $tamilNaduLocations,
            'total_count' => count($tamilNaduLocations)
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only GET method is allowed'
    ]);
}
?>

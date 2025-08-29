<?php
include 'conn.php';

// Set different warranty periods for different service types
$warranty_updates = [
    // Termite services - 5 years warranty
    "UPDATE service_details SET warranty_period = '5 years' WHERE service_type_name LIKE '%termite%'",
    
    // Cleaning services - 10 days warranty
    "UPDATE service_details SET warranty_period = '10 days' WHERE service_type_name LIKE '%cleaning%'",
    
    // Pest control services - 1 month warranty
    "UPDATE service_details SET warranty_period = '1 month' WHERE service_type_name LIKE '%pest%'",
    
    // General services - 90 days warranty (default)
    "UPDATE service_details SET warranty_period = '90 days' WHERE warranty_period IS NULL OR warranty_period = ''"
];

$results = [];

foreach ($warranty_updates as $query) {
    if ($conn->query($query)) {
        $results[] = "Success: " . $conn->affected_rows . " rows updated";
    } else {
        $results[] = "Error: " . $conn->error;
    }
}

// Display results
echo "Warranty Period Update Results:\n";
foreach ($results as $result) {
    echo $result . "\n";
}

// Show current warranty periods
echo "\nCurrent Warranty Periods:\n";
$sql = "SELECT service_type_name, warranty_period, COUNT(*) as count FROM service_details GROUP BY service_type_name, warranty_period ORDER BY service_type_name";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['service_type_name'] . " - " . $row['warranty_period'] . " (" . $row['count'] . " entries)\n";
    }
} else {
    echo "No service details found.\n";
}

$conn->close();
?>

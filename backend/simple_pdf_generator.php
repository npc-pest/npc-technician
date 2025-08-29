<?php
// Simple PDF Generator for NPC Services
// This creates a basic PDF without external dependencies

function generateSimplePDF($userName, $bookingId, $serviceName, $totalAmount, $serviceDate, $timeSlot, $serviceAddress) {
    try {
        // Create a basic PDF structure
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Catalog\n";
        $pdf .= "/Pages 2 0 R\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n";
        
        $pdf .= "2 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Pages\n";
        $pdf .= "/Kids [3 0 R]\n";
        $pdf .= "/Count 1\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n";
        
        $pdf .= "3 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Page\n";
        $pdf .= "/Parent 2 0 R\n";
        $pdf .= "/MediaBox [0 0 612 792]\n";
        $pdf .= "/Contents 4 0 R\n";
        $pdf .= "/Resources <<\n";
        $pdf .= "/Font <<\n";
        $pdf .= "/F1 5 0 R\n";
        $pdf .= ">>\n";
        $pdf .= ">>\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n";
        
        // Create content stream
        $content = "BT\n";
        $content .= "/F1 12 Tf\n";
        $content .= "50 750 Td\n";
        $content .= "(NPC Services - Invoice) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Invoice #: $bookingId) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Customer: $userName) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Service: $serviceName) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Amount: ₹$totalAmount) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Date: $serviceDate) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Time: $timeSlot) Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "(Address: $serviceAddress) Tj\n";
        $content .= "0 -40 Td\n";
        $content .= "(Thank you for choosing NPC Services!) Tj\n";
        $content .= "ET\n";
        
        $pdf .= "4 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Length " . strlen($content) . "\n";
        $pdf .= ">>\n";
        $pdf .= "stream\n";
        $pdf .= $content;
        $pdf .= "endstream\n";
        $pdf .= "endobj\n";
        
        // Font object
        $pdf .= "5 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Font\n";
        $pdf .= "/Subtype /Type1\n";
        $pdf .= "/BaseFont /Helvetica\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n";
        
        // Cross-reference table
        $xref_offset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 6\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= "0000000009 00000 n \n";
        $pdf .= "0000000078 00000 n \n";
        $pdf .= "0000000127 00000 n \n";
        $pdf .= "0000000256 00000 n \n";
        $pdf .= "0000000456 00000 n \n";
        
        // Trailer
        $pdf .= "trailer\n";
        $pdf .= "<<\n";
        $pdf .= "/Size 6\n";
        $pdf .= "/Root 1 0 R\n";
        $pdf .= ">>\n";
        $pdf .= "startxref\n";
        $pdf .= $xref_offset . "\n";
        $pdf .= "%%EOF\n";
        
        return $pdf;
        
    } catch (Exception $e) {
        error_log("Simple PDF generation error: " . $e->getMessage());
        return false;
    }
}

// Test function
if (isset($_GET['test'])) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="test_invoice.pdf"');
    
    $pdf = generateSimplePDF(
        'Test User',
        'TEST123',
        'Test Service',
        '1000',
        '2025-08-12',
        '10:00 AM',
        'Test Address'
    );
    
    echo $pdf;
    exit;
}
?>

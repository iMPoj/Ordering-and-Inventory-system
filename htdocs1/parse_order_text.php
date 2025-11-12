<?php
// FILE: parse_order_text.php
header('Content-Type: application/json');

// --- Helper Functions ---
function find_value($pattern, $text) {
    return preg_match($pattern, $text, $matches) ? trim($matches[1]) : null;
}

function parse_nutri_items($text) {
    $items = [];
    // This regex is specifically for the Nutri PO format and remains unchanged.
    $item_blocks = preg_split('/(?=\d{7}VENDOR ITEM CODE\s*:)/', $text);
    array_shift($item_blocks); 

    foreach ($item_blocks as $block) {
        preg_match('/(\d{7,})VENDOR ITEM CODE\s*:\s*(\d*)\s*BARCODE\s*:/s', $block, $code_matches);
        preg_match('/BARCODE\s*:\s*(.*?)\s*(\d{8})\s+/s', $block, $desc_match);
        preg_match('/(PC|PK|BX|SET)\s+([0-9,]+)/i', $block, $qty_match);

        $vendorCode = $code_matches[1] ?? null;
        $barcode = $code_matches[2] ?? null;
        $description = preg_replace('/\s+/', ' ', trim($desc_match[1] ?? ''));
        $quantity = isset($qty_match[2]) ? (int)str_replace(',', '', trim($qty_match[2])) : 0;

        if ($vendorCode && $quantity > 0 && !empty($description)) {
            $items[] = [
                'vendorCode' => $vendorCode,
                'barcode' => $barcode ?: null,
                'description' => $description,
                'quantity' => $quantity
            ];
        }
    }
    return $items;
}

function parse_health_hygiene_items($text) {
    $items = [];
    $lines = array_map('trim', explode("\n", $text));
    
    foreach ($lines as $i => $line) {
        // Find the "data" line which is the most reliable anchor.
        if (preg_match('/(\d{8})\s+([0-9,.]+)\s+(PC|PK|BX|SET|BOXOF\d+|PKOF\d+)\s+([0-9,]+)\s+0\s+0/i', $line, $data_matches)) {
            
            $quantity = (int)str_replace(',', '', $data_matches[4]);
            $description_part_from_data_line = trim(preg_replace('/(\d{8}).*/', '', $line));
            
            $description = '';
            $vendorCode = null;
            $barcode = null;

            // Look backwards from the data line to find the description and codes
            $lookback_text = '';
            for ($j = 1; $j <= 3; $j++) { // Look back up to 3 lines
                if (isset($lines[$i - $j])) {
                    $prev_line = $lines[$i - $j];
                    if (strpos($prev_line, 'ITEM NO DESCRIPTION COST') !== false) break;
                    $lookback_text = $prev_line . "\n" . $lookback_text;
                    if (strpos($prev_line, 'VENDOR ITEM CODE') !== false || strpos($prev_line, 'BARCODE') !== false) {
                        break; 
                    }
                }
            }
            
            // Rule: Find a number on the left of "VENDOR ITEM CODE"
            $vendorCode = find_value('/^(\d+)\s*VENDOR ITEM CODE/i', $lookback_text);
            
            // Rule: Find a number on the left of "BARCODE"
            $barcode = find_value('/(\d+)\s*BARCODE/i', $lookback_text);

            // Rule: Description is the text between "BARCODE :" and the start of the data line
            preg_match('/BARCODE\s*:\s*(.*)/is', $lookback_text, $desc_match);
            $description = trim($desc_match[1] ?? '');

            // Combine description parts and clean up
            $full_description = trim($description . ' ' . $description_part_from_data_line);
            $full_description = preg_replace('/\s+/', ' ', $full_description);

            // Rule: Prioritize Vendor code over barcode
            if (empty($vendorCode) && !empty($barcode)) {
                $vendorCode = $barcode;
                $barcode = null;
            }

            if ($vendorCode && $quantity > 0 && !empty($full_description)) {
                $items[] = [
                    'vendorCode' => $vendorCode,
                    'barcode' => $barcode,
                    'description' => $full_description,
                    'quantity' => $quantity,
                ];
            }
        }
    }
    return $items;
}


// --- Main Logic ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['text'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$text = $_POST['text'];
$supplier = find_value('/Supplier Details\s*([^\n\r]+)/i', $text);
$items = [];

// Choose the parsing strategy based on the supplier name in the text
if (strpos($supplier, 'O.T.C') !== false) {
    $items = parse_health_hygiene_items($text);
} else {
    $items = parse_nutri_items($text);
}

$data = [
    'poNumber' => find_value('/PO No\.\s*:\s*([A-Z0-9\-]+)/i', $text),
    'customerName' => find_value('/(ROJON PHARMACY CORPORATION|ROSE PHARMACY INCORPORATED)/i', $text),
    'shipTo' => find_value('/Delivery\/Ship To\s*([^\n\r]+)/i', $text),
    'location' => find_value('/WAREHOUSE\s+([A-Z]+)/i', $text),
    'bu' => null,
    'items' => $items
];

if (!$data['poNumber'] || !$data['customerName'] || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Could not parse required fields. Please check the pasted text. Found ' . count($items) . ' items.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'PDF text parsed successfully.',
    'data' => $data
]);
?>


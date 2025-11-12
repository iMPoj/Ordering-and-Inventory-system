<?php
// Set up a global error handler to ensure we always output JSON
header('Content-Type: application/json');

function shutdown_handler() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // Clean any accidental output
        if (ob_get_length()) ob_clean();
        
        $response = [
            'status' => 'error',
            'message' => 'A fatal server error occurred.',
            'debug' => "Error: {$error['message']} in {$error['file']} on line {$error['line']}"
        ];
        echo json_encode($response);
        exit();
    }
}

register_shutdown_function('shutdown_handler');
ob_start(); // Start output buffering to catch any stray echoes or notices

include 'db.php';

// Main logic wrapped in a try-catch block
try {
    $response = ['status' => 'error', 'message' => 'No files were uploaded.'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $upload_errors = [];
        if (!empty($_FILES['davao_files'])) {
            $conn->query("TRUNCATE TABLE davao_orders");
            $conn->query("TRUNCATE TABLE davao_byroute");
            $conn->query("TRUNCATE TABLE davao_bysku");
            $upload_errors = processUploadedFiles($_FILES['davao_files'], 'davao', $conn);
            $location = 'Davao';
        } elseif (!empty($_FILES['gensan_files'])) {
            $conn->query("TRUNCATE TABLE gensan_orders");
            $conn->query("TRUNCATE TABLE gensan_byroute");
            $conn->query("TRUNCATE TABLE gensan_bysku");
            $upload_errors = processUploadedFiles($_FILES['gensan_files'], 'gensan', $conn);
            $location = 'Gensan';
        }

        if (isset($location)) {
            if (empty($upload_errors)) {
                $response = ['status' => 'success', 'message' => "$location data uploaded successfully!"];
            } else {
                $response['message'] = "Errors occurred during $location data upload.";
                $response['debug'] = implode("\n", $upload_errors);
            }
        }
    }
} catch (mysqli_sql_exception $e) {
    // Catch database-specific errors
    $response['message'] = 'A database error occurred.';
    $response['debug'] = $e->getMessage();
} catch (Exception $e) {
    // Catch other general errors
    $response['message'] = 'A server exception occurred.';
    $response['debug'] = $e->getMessage();
}

function processUploadedFiles($files, $location, $conn) {
    $errors = [];
    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = $files['name'][$i];
        $tmpName = $files['tmp_name'][$i];
        
        $table = '';
        $numeric_columns = []; // --- ADDED: To identify numeric columns ---
        if (strpos($fileName, 'BYROUTE.CSV') !== false) {
            $table = $location . '_byroute';
            $numeric_columns = ['invoice_amt', 'qty_fillrate'];
        } elseif (strpos($fileName, 'BYSKU.CSV') !== false) {
            $table = $location . '_bysku';
            $numeric_columns = ['order_qty_ea', 'invoice_quantity', 'order_amt', 'invoice_amt'];
        } elseif (strpos($fileName, 'ORDER.CSV') !== false) {
            $table = $location . '_orders';
        }

        if ($table) {
            // --- MODIFIED: Pass numeric columns to the upload function ---
            $result = uploadCSV($tmpName, $table, $conn, $numeric_columns);
            if ($result !== true) {
                $errors[] = "Error in file '$fileName': " . $result;
            }
        } else {
            $errors[] = "Could not determine table for file '$fileName'.";
        }
    }
    return $errors;
}

// --- MODIFIED: This function is heavily updated to clean data before insertion ---
function uploadCSV($file, $table, $conn, $numeric_columns = []) {
    if (!is_uploaded_file($file)) return "File not uploaded correctly.";
    
    $handle = fopen($file, "r");
    if ($handle === FALSE) return "Could not open the file.";

    $result = $conn->query("SHOW COLUMNS FROM `$table`");
    if(!$result) return "Database Error: Could not find table '$table'.";

    $columns = [];
    $column_map = [];
    $result->fetch_assoc(); // Skip 'id'
    $i = 0;
    while ($row = $result->fetch_assoc()) {
        $columns[] = '`' . $row['Field'] . '`';
        // Map the column name to its position in the CSV
        $column_map[$i] = $row['Field'];
        $i++;
    }
    $column_count = count($columns);
    $column_sql = implode(",", $columns);

    fgetcsv($handle); // Skip header row

    $line_number = 1;
    while (($data = fgetcsv($handle, 2000, "\t")) !== FALSE) {
        $line_number++;
        if (count($data) == 1 && (is_null($data[0]) || $data[0] === '')) continue; // Skip empty lines

        if (count($data) != $column_count) {
            fclose($handle);
            return "Column count mismatch on line $line_number. The database table '$table' expects $column_count columns, but the file has " . count($data) . ".";
        }

        $values = [];
        for ($j = 0; $j < $column_count; $j++) {
            $value = trim($data[$j]);
            $column_name = $column_map[$j];

            // If the column is supposed to be numeric, clean it
            if (in_array($column_name, $numeric_columns)) {
                $cleaned_value = filter_var(str_replace(',', '', $value), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $values[] = is_numeric($cleaned_value) ? $cleaned_value : 0;
            } else { // Otherwise, treat it as a string
                $values[] = "'" . $conn->real_escape_string($value) . "'";
            }
        }
        
        $sql = "INSERT INTO `$table` ($column_sql) VALUES (" . implode(",", $values) . ")";
        
        if (!$conn->query($sql)) {
            fclose($handle);
            return "Database insert error on line $line_number: " . $conn->error;
        }
    }

    fclose($handle);
    return true;
}

// Clean the output buffer and echo the final JSON response
ob_end_clean();
echo json_encode($response);
?>
<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Include the database connection file
require_once __DIR__ . '/../config/database.php';

// Define log file path (ensure the path is writable)
$logFile = __DIR__ . '/debug.log';

/**
 * Custom function to log messages to a file
 */
function logToFile($message, $logFile) {
    $date = date('Y-m-d H:i:s');
    $logEntry = "[$date] $message" . PHP_EOL;

    $file = fopen($logFile, 'a');
    if (!$file) {
        error_log("Failed to open log file: $logFile");
        return false;
    }
    if (!fwrite($file, $logEntry)) {
        error_log("Failed to write to log file: $logFile");
        fclose($file);
        return false;
    }
    fclose($file);
    return true;
}

// Debug: Ensure database connection is established
if (!$conn) {
    logToFile("Database connection failed.", $logFile);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Debugging: Log the raw input
$rawInput = file_get_contents("php://input");
logToFile("Raw input: " . $rawInput, $logFile);

$data = json_decode($rawInput, true);

// Debugging: Log the decoded data
logToFile("Decoded data: " . print_r($data, true), $logFile);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data.']);
    logToFile("Invalid JSON data. " . print_r($data, true), $logFile);
    exit;
}

if (!isset($data['id']) || !isset($data['play_card'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$id = $data['id'];
$playcard = $data['play_card'];

// Get current date
$currentDate = (new DateTime('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d');

try {
    // Debug: Log query parameters
    logToFile("Query Params - ID: $id, Playcard: $playcard, Date: $currentDate", $logFile);

    // Check if the student exists in the users table
    $sqlCheckStudent = "SELECT id FROM users WHERE id = :id AND play_card = :playcard";
    $stmtCheckStudent = $conn->prepare($sqlCheckStudent);
    $stmtCheckStudent->bindParam(':id', $id, PDO::PARAM_STR);
    $stmtCheckStudent->bindParam(':playcard', $playcard, PDO::PARAM_STR);

    if (!$stmtCheckStudent->execute()) {
        $errorInfo = $stmtCheckStudent->errorInfo();
        logToFile("SQL Execution failed: " . print_r($errorInfo, true), $logFile);
        echo json_encode(['success' => false, 'message' => 'Failed to check student.']);
        exit;
    }

    if ($stmtCheckStudent->rowCount() === 0) {
        logToFile("Student not found: ID=$id, Playcard=$playcard", $logFile);
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }

    // Check attendance for the current date
    $sqlCheckAttendance = "SELECT session1, session2, session3, session4, session5
                           FROM attendance
                           WHERE user_id = :id AND date = :currentDate";
    $stmtCheckAttendance = $conn->prepare($sqlCheckAttendance);
    $stmtCheckAttendance->bindParam(':id', $id, PDO::PARAM_STR);
    $stmtCheckAttendance->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);

    if (!$stmtCheckAttendance->execute()) {
        $errorInfo = $stmtCheckAttendance->errorInfo();
        logToFile("SQL Execution failed: " . print_r($errorInfo, true), $logFile);
        echo json_encode(['success' => false, 'message' => 'Failed to check attendance.']);
        exit;
    }

    // Debug: Log number of rows found
    $rowCount = $stmtCheckAttendance->rowCount();
    logToFile("Row Count: " . $rowCount, $logFile);

    if ($rowCount > 0) {
        $row = $stmtCheckAttendance->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Log fetched row
        logToFile("Fetched Row: " . print_r($row, true), $logFile);

        // Prepare attendance data
        $attendance = [
            'session1' => (bool)($row['session1'] ?? false),
            'session2' => (bool)($row['session2'] ?? false),
            'session3' => (bool)($row['session3'] ?? false),
            'session4' => (bool)($row['session4'] ?? false),
            'session5' => (bool)($row['session5'] ?? false)
        ];
        echo json_encode(['success' => true, 'attendance' => $attendance]);
    } else {
        logToFile("No attendance record found for ID: $id, Playcard: $playcard", $logFile);
        echo json_encode(['success' => false, 'message' => 'No attendance record found for today.']);
    }
} catch (PDOException $e) {
    logToFile("Database error: " . $e->getMessage(), $logFile);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
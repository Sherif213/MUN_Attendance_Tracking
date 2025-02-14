<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Exit script for preflight requests
    exit;
}

// Include the database connection file
require_once __DIR__ . '/../config/database.php';

// Debugging: Log the raw input
$rawInput = file_get_contents("php://input");
error_log("Raw input: " . $rawInput);

$data = json_decode($rawInput, true);

// Debugging: Log the decoded data
error_log("Decoded data: " . print_r($data, true));

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data.']);
    exit;
}

if (!isset($data['id']) || !isset($data['playcard'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$id = $data['id'];
$playcard = $data['playcard'];

// Get current date
$currentDate = (new DateTime('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d');

try {
    // Check attendance for the current date
    $sql = "SELECT u.id, a.session1, a.session2, a.session3 ,a.session4,a.session5
            FROM users u
            LEFT JOIN attendance a ON u.id = a.user_id AND a.date = :currentDate
            WHERE u.id = :id AND u.play_card = :playcard";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':playcard', $playcard, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $attendance = [
            'session1' => (bool)$row['session1'],
            'session2' => (bool)$row['session2'],
            'session3' => (bool)$row['session3'],
            'session4' => (bool)$row['session4'],
            'session5' => (bool)$row['session5']
        ];
        echo json_encode(['success' => true, 'attendance' => $attendance]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No attendance record found.']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
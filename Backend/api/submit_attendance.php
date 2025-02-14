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

if (!isset($data['id']) || !isset($data['playcard']) || !isset($data['code'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$id = $data['id'];
$playcard = $data['playcard'];
$code = $data['code'];

// Get current date and time
$currentDateTime = new DateTime('now', new DateTimeZone('Europe/Istanbul'));
$currentDate = $currentDateTime->format('Y-m-d');
$currentTime = $currentDateTime->format('H:i:s');

// Determine the current session based on time
$config = require __DIR__ . '/../config/config.php';
$sessions = $config['sessions'];

function getCurrentSession($currentTime, $sessions)
{
    foreach ($sessions as $session => $time) {
        if ($currentTime >= $time['start'] && $currentTime < $time['end']) {
            return $session;
        }
    }
    return null;
}

$session = getCurrentSession($currentTime, $sessions);
if (!$session) {
    echo json_encode(['success' => false, 'message' => 'No active session at this time.']);
    exit;
}


try {
    // Check if the user exists and fetch their committee
    $sql = "SELECT id, committee FROM users WHERE id = :id AND play_card = :playcard";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_STR);
    $stmt->bindParam(':playcard', $playcard, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $row['id'];
        $committee = $row['committee'];

        // Fetch the valid code for the committee and session
        $codeSql = "SELECT {$session}_code AS valid_code FROM committee_codes WHERE committee = :committee AND date = :currentDate";
        $codeStmt = $conn->prepare($codeSql);
        $codeStmt->bindParam(':committee', $committee, PDO::PARAM_STR);
        $codeStmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
        $codeStmt->execute();

        if ($codeStmt->rowCount() > 0) {
            $codeRow = $codeStmt->fetch(PDO::FETCH_ASSOC);
            $validCode = $codeRow['valid_code'];

            // Validate the code
            if ($code !== $validCode) {
                echo json_encode(['success' => false, 'message' => 'Invalid code for this session.']);
                exit;
            }

            // Check if attendance record for today already exists
            $attendanceSql = "SELECT * FROM attendance WHERE user_id = :userId AND date = :currentDate";
            $attendanceStmt = $conn->prepare($attendanceSql);
            $attendanceStmt->bindParam(':userId', $userId, PDO::PARAM_STR);
            $attendanceStmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
            $attendanceStmt->execute();

            if ($attendanceStmt->rowCount() > 0) {
                // Update existing attendance record for the current session
                $updateSql = "UPDATE attendance SET $session = 1 WHERE user_id = :userId AND date = :currentDate";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bindParam(':userId', $userId, PDO::PARAM_STR);
                $updateStmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
                $updateStmt->execute();

                if ($updateStmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'attendance' => [
                        'session1' => true,
                        'session2' => true,
                        'session3' => true,
                        'session4' => true,
                        'session5' => true
                    ]]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to mark attendance.']);
                }
            } else {
                // Insert new attendance record for the current session
                $insertSql = "INSERT INTO attendance (user_id, date, $session) VALUES (:userId, :currentDate, 1)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bindParam(':userId', $userId, PDO::PARAM_STR);
                $insertStmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
                $insertStmt->execute();

                if ($insertStmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'attendance' => [
                        'session1' => true,
                        'session2' => true,
                        'session3' => true,
                        'session4' => true,
                        'session5' => true
                    ]]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to mark attendance.']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No valid code found for this committee and session.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID or Playcard.']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
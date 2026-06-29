<?php
// Create a plant reservation
require_once '../../config/db.php';
require_once '../../lib/response.php';
require_once '../../lib/auth.php';
require_once '../../lib/validators.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Only POST requests allowed', 405);
}

// Verify authentication
$user = Auth::getCurrentUser($conn);
if (!$user) {
    Response::unauthorized();
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['plant_id'];
$missing = Validator::checkRequired($data, $required);
if (!empty($missing)) {
    Response::validation(['missing_fields' => $missing]);
}

$plantId = intval($data['plant_id']);
$studentId = $user['student_id'];
$eventId = isset($data['event_id']) ? intval($data['event_id']) : null;

// Check if plant exists
$plantQuery = "SELECT plant_id FROM ecotrace_plants WHERE plant_id = ?";
$plantStmt = $conn->prepare($plantQuery);
$plantStmt->bind_param("i", $plantId);
$plantStmt->execute();
$plantResult = $plantStmt->get_result();

if ($plantResult->num_rows === 0) {
    Response::notFound();
}

// Check if plant is already reserved by someone else (active reservations only)
$reserveQuery = "SELECT reservation_id, expires_at FROM ecotrace_plant_reservations 
                 WHERE plant_id = ? AND is_active = TRUE AND expires_at > NOW()";
$reserveStmt = $conn->prepare($reserveQuery);
$reserveStmt->bind_param("i", $plantId);
$reserveStmt->execute();
$reserveResult = $reserveStmt->get_result();

if ($reserveResult->num_rows > 0) {
    Response::error('Plant is already reserved by another student', 409);
}

// Check if already reserved by this user
$userReserveQuery = "SELECT reservation_id FROM ecotrace_plant_reservations 
                     WHERE plant_id = ? AND student_id = ? AND is_active = TRUE AND expires_at > NOW()";
$userReserveStmt = $conn->prepare($userReserveQuery);
$userReserveStmt->bind_param("ii", $plantId, $studentId);
$userReserveStmt->execute();
$userReserveResult = $userReserveStmt->get_result();

if ($userReserveResult->num_rows > 0) {
    Response::error('You already have an active reservation for this plant', 409);
}

// Create reservation (7-day lock)
$expiresAt = date('Y-m-d H:i:s', time() + RESERVATION_LOCK_DURATION);

$insertQuery = "INSERT INTO ecotrace_plant_reservations (plant_id, student_id, event_id, expires_at, is_active) 
                VALUES (?, ?, ?, ?, TRUE)";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("iiis", $plantId, $studentId, $eventId, $expiresAt);

if (!$insertStmt->execute()) {
    Response::error('Failed to create reservation', 500);
}

Response::success([
    'reservation_id' => $conn->insert_id,
    'plant_id' => $plantId,
    'student_id' => $studentId,
    'event_id' => $eventId,
    'expires_at' => $expiresAt,
    'is_active' => true
], 'Plant reserved successfully');

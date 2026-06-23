<?php
// Submit plant verification
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
$required = ['plant_id', 'event_id', 'health_status', 'plant_stage'];
$missing = Validator::checkRequired($data, $required);
if (!empty($missing)) {
    Response::validation(['missing_fields' => $missing]);
}

$plantId = intval($data['plant_id']);
$eventId = intval($data['event_id']);
$healthStatus = strtolower($data['health_status']);
$plantStage = strtolower($data['plant_stage']);
$heightCm = isset($data['height_cm']) ? floatval($data['height_cm']) : null;
$circumferenceCm = isset($data['circumference_cm']) ? floatval($data['circumference_cm']) : null;
$photoUrl = isset($data['photo_url']) ? $data['photo_url'] : null;
$notes = isset($data['notes']) ? Validator::sanitize($data['notes']) : null;

// Validate health status
if (!Validator::isValidHealthStatus($healthStatus)) {
    Response::validation(['health_status' => 'Invalid health status']);
}

// Validate plant stage
if (!Validator::isValidPlantStage($plantStage)) {
    Response::validation(['plant_stage' => 'Invalid plant stage']);
}

// Check if plant exists
$plantQuery = "SELECT id FROM ecotag_plants WHERE id = ?";
$plantStmt = $conn->prepare($plantQuery);
$plantStmt->bind_param("i", $plantId);
$plantStmt->execute();
if ($plantStmt->get_result()->num_rows === 0) {
    Response::notFound();
}

// Check if event exists
$eventQuery = "SELECT id FROM ecotag_events WHERE id = ?";
$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param("i", $eventId);
$eventStmt->execute();
if ($eventStmt->get_result()->num_rows === 0) {
    Response::notFound();
}

// Insert verification (immutable record)
$insertQuery = "INSERT INTO ecotrace_verifications 
                (plantId, studentId, eventId, healthStatus, heightCm, circumferenceCm, plantStage, photoUrl, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param(
    "iiisddss",
    $plantId,
    $user['id'],
    $eventId,
    $healthStatus,
    $heightCm,
    $circumferenceCm,
    $plantStage,
    $photoUrl,
    $notes
);

if (!$insertStmt->execute()) {
    Response::error('Failed to submit verification', 500);
}

$verificationId = $conn->insert_id;

// Update plant status
$updateQuery = "UPDATE ecotag_plants SET status = 'verified' WHERE id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("i", $plantId);
$updateStmt->execute();

// Delete any active reservation for this plant by this user
$deleteQuery = "DELETE FROM ecotrace_reservations WHERE plantId = ? AND studentId = ?";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("ii", $plantId, $user['id']);
$deleteStmt->execute();

Response::success([
    'verification_id' => $verificationId,
    'plant_id' => $plantId,
    'submitted_at' => date('Y-m-d H:i:s')
], 'Verification submitted successfully');
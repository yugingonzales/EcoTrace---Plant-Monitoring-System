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
$required = ['plant_id', 'event_id', 'health_status', 'plant_stage', 'latitude', 'longitude'];
$missing = Validator::checkRequired($data, $required);
if (!empty($missing)) {
    Response::validation(['missing_fields' => $missing]);
}

$plantId = intval($data['plant_id']);
$eventId = intval($data['event_id']);
$studentId = $user['student_id'];
$healthStatus = strtolower($data['health_status']);
$plantStage = strtolower($data['plant_stage']);
$latitude = floatval($data['latitude']);
$longitude = floatval($data['longitude']);

$heightCm = isset($data['height_cm']) ? floatval($data['height_cm']) : null;
$circumferenceCm = isset($data['circumference_cm']) ? floatval($data['circumference_cm']) : null;
$canopyDiameterCm = isset($data['canopy_diameter_cm']) ? floatval($data['canopy_diameter_cm']) : null;
$leafCondition = isset($data['leaf_condition']) ? strtolower($data['leaf_condition']) : null;
$soilCondition = isset($data['soil_condition']) ? strtolower($data['soil_condition']) : null;
$hasPests = isset($data['has_pests']) ? (bool)$data['has_pests'] : false;
$hasDisease = isset($data['has_disease']) ? (bool)$data['has_disease'] : false;
$needsWater = isset($data['needs_water']) ? (bool)$data['needs_water'] : false;
$needsFertilizer = isset($data['needs_fertilizer']) ? (bool)$data['needs_fertilizer'] : false;
$verificationNotes = isset($data['verification_notes']) ? Validator::sanitize($data['verification_notes']) : null;
$weatherCondition = isset($data['weather_condition']) ? Validator::sanitize($data['weather_condition']) : null;
$temperatureCelsius = isset($data['temperature_celsius']) ? floatval($data['temperature_celsius']) : null;
$photoUrls = isset($data['photo_urls']) ? $data['photo_urls'] : [];


// Validate health status
if (!Validator::isValidHealthStatus($healthStatus)) {
    Response::validation(['health_status' => 'Invalid health status']);
}

// Validate plant stage
if (!Validator::isValidPlantStage($plantStage)) {
    Response::validation(['plant_stage' => 'Invalid plant stage']);
}

// Validate coordinates
if (!Validator::isValidCoordinates($latitude, $longitude)) {
    Response::validation(['latitude' => 'Invalid latitude', 'longitude' => 'Invalid longitude']);
}

// Check if plant exists
$plantQuery = "SELECT plant_id FROM ecotrace_plants WHERE plant_id = ?";
$plantStmt = $conn->prepare($plantQuery);
$plantStmt->bind_param("i", $plantId);
$plantStmt->execute();
if ($plantStmt->get_result()->num_rows === 0) {
    Response::notFound('Plant not found');
}

// Check if event exists
$eventQuery = "SELECT event_id FROM ecotrace_events WHERE event_id = ?";
$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param("i", $eventId);
$eventStmt->execute();
if ($eventStmt->get_result()->num_rows === 0) {
    Response::notFound('Event not found');
}

// Insert verification
$insertQuery = "INSERT INTO ecotrace_plant_verifications 
                (plant_id, student_id, event_id, health_status, height_cm, circumference_cm, 
                 canopy_diameter_cm, plant_stage, leaf_condition, soil_condition, has_pests, 
                 has_disease, needs_water, needs_fertilizer, verification_notes, 
                 weather_condition, temperature_celsius, latitude, longitude) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param(
    "iiisddssssiiiisssd",
    $plantId,
    $studentId,
    $eventId,
    $healthStatus,
    $heightCm,
    $circumferenceCm,
    $canopyDiameterCm,
    $plantStage,
    $leafCondition,
    $soilCondition,
    $hasPests,
    $hasDisease,
    $needsWater,
    $needsFertilizer,
    $verificationNotes,
    $weatherCondition,
    $temperatureCelsius,
    $latitude,
    $longitude
);

if (!$insertStmt->execute()) {
    Response::error('Failed to submit verification: ' . $conn->error, 500);
}

$verificationId = $conn->insert_id;

// Insert photos
if (!empty($photoUrls)) {
    $photoInsertQuery = "INSERT INTO ecotrace_verification_photos (verification_id, photo_url) VALUES (?, ?)";
    $photoStmt = $conn->prepare($photoInsertQuery);
    foreach ($photoUrls as $url) {
        $photoStmt->bind_param("is", $verificationId, $url);
        $photoStmt->execute();
    }
}

// Update plant status and verification count
$updatePlantQuery = "UPDATE ecotrace_plants 
                     SET plant_status = 'verified', 
                         verification_count = verification_count + 1, 
                         last_verified_at = NOW() 
                     WHERE plant_id = ?";
$updatePlantStmt = $conn->prepare($updatePlantQuery);
$updatePlantStmt->bind_param("i", $plantId);
$updatePlantStmt->execute();

// Deactivate any active reservation for this plant by this user
$deactivateReservationQuery = "UPDATE ecotrace_plant_reservations 
                               SET is_active = FALSE 
                               WHERE plant_id = ? AND student_id = ? AND is_active = TRUE";
$deactivateReservationStmt = $conn->prepare($deactivateReservationQuery);
$deactivateReservationStmt->bind_param("ii", $plantId, $studentId);
$deactivateReservationStmt->execute();

Response::success([
    'verification_id' => $verificationId,
    'plant_id' => $plantId,
    'submitted_at' => date('Y-m-d H:i:s')
], 'Verification submitted successfully');

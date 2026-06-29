<?php
// Get plant details
require_once '../../config/db.php';
require_once '../../lib/response.php';
require_once '../../lib/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Only GET requests allowed', 405);
}

// Verify authentication
$user = Auth::getCurrentUser($conn);
if (!$user) {
    Response::unauthorized();
}

if (!isset($_GET['id'])) {
    Response::validation(['id' => 'Plant ID is required']);
}

$plantId = intval($_GET['id']);

// Get plant details
$query = "SELECT plant_id, latitude, longitude, location_address, planted_date, 
                 plant_status, plant_species, verification_count, last_verified_at 
          FROM ecotrace_plants 
          WHERE plant_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $plantId);
$stmt->execute();
$result = $stmt->get_result();
$plant = $result->fetch_assoc();

if (!$plant) {
    Response::notFound();
}

// Get latest verification for this plant with photos
$verifyQuery = "SELECT verification_id, health_status, height_cm, circumference_cm, 
                       canopy_diameter_cm, plant_stage, leaf_condition, soil_condition,
                       has_pests, has_disease, needs_water, needs_fertilizer,
                       verification_notes, weather_condition, temperature_celsius, verified_at 
                FROM ecotrace_plant_verifications 
                WHERE plant_id = ? 
                ORDER BY verified_at DESC 
                LIMIT 1";
$verifyStmt = $conn->prepare($verifyQuery);
$verifyStmt->bind_param("i", $plantId);
$verifyStmt->execute();
$verifyResult = $verifyStmt->get_result();
$lastVerification = $verifyResult->fetch_assoc();

// Get photos for latest verification if exists
if ($lastVerification) {
    $photoQuery = "SELECT photo_id, photo_url, photo_type, uploaded_at 
                   FROM ecotrace_verification_photos 
                   WHERE verification_id = ?";
    $photoStmt = $conn->prepare($photoQuery);
    $photoStmt->bind_param("i", $lastVerification['verification_id']);
    $photoStmt->execute();
    $photoResult = $photoStmt->get_result();
    
    $photos = [];
    while ($photo = $photoResult->fetch_assoc()) {
        $photos[] = $photo;
    }
    $lastVerification['photos'] = $photos;
}

// Check if plant is reserved by current user
$reserveQuery = "SELECT reservation_id, expires_at FROM ecotrace_plant_reservations 
                 WHERE plant_id = ? AND student_id = ? AND is_active = TRUE AND expires_at > NOW()";
$reserveStmt = $conn->prepare($reserveQuery);
$reserveStmt->bind_param("ii", $plantId, $user['student_id']);
$reserveStmt->execute();
$reserveResult = $reserveStmt->get_result();
$reservation = $reserveResult->fetch_assoc();

$plantData = [
    'plant_id' => $plant['plant_id'],
    'latitude' => $plant['latitude'],
    'longitude' => $plant['longitude'],
    'location_address' => $plant['location_address'],
    'planted_date' => $plant['planted_date'],
    'plant_status' => $plant['plant_status'],
    'plant_species' => $plant['plant_species'],
    'verification_count' => $plant['verification_count'],
    'last_verified_at' => $plant['last_verified_at'],
    'last_verification' => $lastVerification,
    'is_reserved_by_me' => !empty($reservation),
    'reservation' => $reservation
];

Response::success($plantData, 'Plant details retrieved successfully');

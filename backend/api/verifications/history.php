<?php
// Get verification history
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

$eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

if ($limit < 1 || $limit > 100) {
    $limit = 50;
}

if ($offset < 0) {
    $offset = 0;
}

// Build query
$query = "SELECT v.verification_id, v.plant_id, v.event_id, v.health_status, v.height_cm, 
                 v.circumference_cm, v.canopy_diameter_cm, v.plant_stage, v.leaf_condition, 
                 v.soil_condition, v.has_pests, v.has_disease, v.needs_water, v.needs_fertilizer,
                 v.verification_notes, v.weather_condition, v.temperature_celsius, v.verified_at,
                 p.latitude, p.longitude, p.location_address, p.plant_species
          FROM ecotrace_plant_verifications v
          JOIN ecotrace_plants p ON v.plant_id = p.plant_id
          WHERE v.student_id = ?";

$params = [$user['student_id']];
$types = "i";

if ($eventId !== null) {
    $query .= " AND v.event_id = ?";
    $params[] = $eventId;
    $types .= "i";
}

$query .= " ORDER BY v.verified_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);

if (!$stmt) {
    Response::error('Database error: ' . $conn->error, 500);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$verifications = [];
while ($row = $result->fetch_assoc()) {
    // Get photos for this verification
    $photoQuery = "SELECT photo_id, photo_url, photo_type, uploaded_at 
                   FROM ecotrace_verification_photos 
                   WHERE verification_id = ?";
    $photoStmt = $conn->prepare($photoQuery);
    $photoStmt->bind_param("i", $row['verification_id']);
    $photoStmt->execute();
    $photoResult = $photoStmt->get_result();
    
    $photos = [];
    while ($photo = $photoResult->fetch_assoc()) {
        $photos[] = $photo;
    }
    $row['photos'] = $photos;

    $verifications[] = $row;
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM ecotrace_plant_verifications WHERE student_id = ?";
$countParams = [$user['student_id']];

if ($eventId !== null) {
    $countQuery .= " AND event_id = ?";
    $countParams[] = $eventId;
}

$countStmt = $conn->prepare($countQuery);
if (!$countStmt) {
    Response::error('Database error: ' . $conn->error, 500);
}

if ($eventId !== null) {
    $countStmt->bind_param("ii", ...$countParams);
} else {
    $countStmt->bind_param("i", $user['student_id']);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$total = $countRow['total'];

Response::success([
    'verifications' => $verifications,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset
], 'Verification history retrieved successfully');
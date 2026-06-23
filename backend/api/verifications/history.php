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
$query = "SELECT v.id, v.plantId, v.eventId, v.healthStatus, v.heightCm, v.circumferenceCm, 
                 v.plantStage, v.photoUrl, v.notes, v.created_at,
                 p.latitude, p.longitude, p.locationAddress
          FROM ecotrace_verifications v
          JOIN ecotag_plants p ON v.plantId = p.id
          WHERE v.studentId = ?";

$params = [$user['id']];
$types = "i";

if ($eventId !== null) {
    $query .= " AND v.eventId = ?";
    $params[] = $eventId;
    $types .= "i";
}

$query .= " ORDER BY v.created_at DESC LIMIT ? OFFSET ?";
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
    $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
    $verifications[] = $row;
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM ecotrace_verifications WHERE studentId = ?";
$countParams = [$user['id']];

if ($eventId !== null) {
    $countQuery .= " AND eventId = ?";
    $countParams[] = $eventId;
}

$countStmt = $conn->prepare($countQuery);
if (!$countStmt) {
    Response::error('Database error: ' . $conn->error, 500);
}

if ($eventId !== null) {
    $countStmt->bind_param("ii", ...$countParams);
} else {
    $countStmt->bind_param("i", $user['id']);
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
<?php
// Check if a plant is reserved
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

if (!isset($_GET['plant_id'])) {
    Response::validation(['plant_id' => 'Plant ID is required']);
}

$plantId = intval($_GET['plant_id']);

// Check for active reservation
$query = "SELECT id, studentId, expiresAt FROM ecotrace_reservations 
          WHERE plantId = ? AND expiresAt > NOW()";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $plantId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    Response::success([
        'is_reserved' => false,
        'reserved_by_me' => false
    ], 'Plant is not reserved');
}

$reservation = $result->fetch_assoc();
$isReservedByMe = $reservation['studentId'] == $user['id'];

Response::success([
    'is_reserved' => true,
    'reserved_by_me' => $isReservedByMe,
    'expires_at' => $reservation['expiresAt']
], 'Reservation status retrieved');
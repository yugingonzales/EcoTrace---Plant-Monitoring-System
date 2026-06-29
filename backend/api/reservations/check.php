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
$query = "SELECT reservation_id, student_id, expires_at FROM ecotrace_plant_reservations 
          WHERE plant_id = ? AND is_active = TRUE AND expires_at > NOW()";
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
$isReservedByMe = $reservation['student_id'] == $user['student_id'];

Response::success([
    'is_reserved' => true,
    'reserved_by_me' => $isReservedByMe,
    'expires_at' => $reservation['expires_at']
], 'Reservation status retrieved');

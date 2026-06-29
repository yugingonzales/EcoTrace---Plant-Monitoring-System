<?php
// Release a reservation
require_once '../../config/db.php';
require_once '../../lib/response.php';
require_once '../../lib/auth.php';

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

if (!isset($data['id'])) {
    Response::validation(['id' => 'Reservation ID is required']);
}

$reservationId = intval($data['reservation_id']);

// Check if reservation exists and belongs to current user
$query = "SELECT reservation_id, plant_id, student_id FROM ecotrace_plant_reservations WHERE reservation_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    Response::notFound();
}

if ($reservation['student_id'] != $user['student_id']) {
    Response::error('Unauthorized', 403);
}

// Deactivate reservation
$updateQuery = "UPDATE ecotrace_plant_reservations SET is_active = FALSE WHERE reservation_id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("i", $reservationId);

if (!$updateStmt->execute()) {
    Response::error('Failed to release reservation', 500);
}

Response::success([], 'Reservation released successfully');
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

$reservationId = intval($data['id']);

// Check if reservation exists and belongs to current user
$query = "SELECT id, plantId, studentId FROM ecotrace_reservations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    Response::notFound();
}

if ($reservation['studentId'] != $user['id']) {
    Response::error('Unauthorized', 403);
}

// Delete reservation
$deleteQuery = "DELETE FROM ecotrace_reservations WHERE id = ?";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("i", $reservationId);

if (!$deleteStmt->execute()) {
    Response::error('Failed to release reservation', 500);
}

Response::success([], 'Reservation released successfully');
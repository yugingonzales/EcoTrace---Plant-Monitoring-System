<?php
// Get event details
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
    Response::validation(['id' => 'Event ID is required']);
}

$eventId = intval($_GET['id']);

// Get event details
$query = "SELECT id, title, description, startDate, endDate, treeCountPerStudent, targetYearBatch, status 
          FROM ecotag_events 
          WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    Response::notFound();
}

$event['startDate'] = date('Y-m-d', strtotime($event['startDate']));
$event['endDate'] = date('Y-m-d', strtotime($event['endDate']));

// Get user's verification count
$countQuery = "SELECT COUNT(*) as verifiedCount FROM ecotrace_verifications 
               WHERE eventId = ? AND studentId = ?";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param("ii", $eventId, $user['id']);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$event['verifiedCount'] = $countRow['verifiedCount'];
$event['targetVerifications'] = $event['treeCountPerStudent'];

Response::success($event, 'Event details retrieved successfully');
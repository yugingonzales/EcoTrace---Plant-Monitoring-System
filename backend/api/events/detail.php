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
$query = "SELECT event_id, event_title, event_description, start_date, end_date, 
                 trees_per_student, target_year_batch, event_status 
          FROM ecotrace_events 
          WHERE event_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    Response::notFound();
}

// Get user's verification count
$countQuery = "SELECT COUNT(*) as verified_count FROM ecotrace_plant_verifications 
               WHERE event_id = ? AND student_id = ?";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param("ii", $eventId, $user['student_id']);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();

$eventData = [
    'event_id' => $event['event_id'],
    'event_title' => $event['event_title'],
    'event_description' => $event['event_description'],
    'start_date' => $event['start_date'],
    'end_date' => $event['end_date'],
    'trees_per_student' => $event['trees_per_student'],
    'target_year_batch' => $event['target_year_batch'],
    'event_status' => $event['event_status'],
    'verified_count' => $countRow['verified_count'],
    'target_verifications' => $event['trees_per_student']
];

Response::success($eventData, 'Event details retrieved successfully');

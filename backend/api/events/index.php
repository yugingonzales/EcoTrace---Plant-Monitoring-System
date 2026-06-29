<?php
// Get all active events
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

// Get active events
$query = "SELECT event_id, event_title, event_description, start_date, end_date, 
                 trees_per_student, target_year_batch, event_status 
          FROM ecotrace_events 
          WHERE event_status = 'active' AND end_date >= CURDATE()
          ORDER BY start_date DESC";

$result = $conn->query($query);

if (!$result) {
    Response::error('Database error: ' . $conn->error, 500);
}

$events = [];
while ($row = $result->fetch_assoc()) {
    // Get verification count for this event and user
    $countQuery = "SELECT COUNT(*) as verified_count FROM ecotrace_plant_verifications 
                   WHERE event_id = ? AND student_id = ?";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param("ii", $row['event_id'], $user['student_id']);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult->fetch_assoc();
    
    $events[] = [
        'event_id' => $row['event_id'],
        'event_title' => $row['event_title'],
        'event_description' => $row['event_description'],
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'trees_per_student' => $row['trees_per_student'],
        'target_year_batch' => $row['target_year_batch'],
        'event_status' => $row['event_status'],
        'verified_count' => $countRow['verified_count']
    ];
}

Response::success(['events' => $events], 'Events retrieved successfully');

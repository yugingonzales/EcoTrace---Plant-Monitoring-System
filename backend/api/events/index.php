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
$query = "SELECT id, title, description, startDate, endDate, treeCountPerStudent, targetYearBatch, status 
          FROM ecotag_events 
          WHERE status = 'active' AND endDate >= CURDATE()
          ORDER BY startDate DESC";

$result = $conn->query($query);

if (!$result) {
    Response::error('Database error: ' . $conn->error, 500);
}

$events = [];
while ($row = $result->fetch_assoc()) {
    $row['startDate'] = date('Y-m-d', strtotime($row['startDate']));
    $row['endDate'] = date('Y-m-d', strtotime($row['endDate']));
    
    // Get verification count for this event and user
    $countQuery = "SELECT COUNT(*) as verifiedCount FROM ecotrace_verifications 
                   WHERE eventId = ? AND studentId = ?";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param("ii", $row['id'], $user['id']);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult->fetch_assoc();
    $row['verifiedCount'] = $countRow['verifiedCount'];
    
    $events[] = $row;
}

Response::success(['events' => $events], 'Events retrieved successfully');
<?php
// Get plant details
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
    Response::validation(['id' => 'Plant ID is required']);
}

$plantId = intval($_GET['id']);

// Get plant details
$query = "SELECT id, latitude, longitude, locationAddress, plantedDate, status 
          FROM ecotag_plants 
          WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $plantId);
$stmt->execute();
$result = $stmt->get_result();
$plant = $result->fetch_assoc();

if (!$plant) {
    Response::notFound();
}

// Get latest verification for this plant
$verifyQuery = "SELECT id, healthStatus, heightCm, circumferenceCm, plantStage, photoUrl, created_at 
                FROM ecotrace_verifications 
                WHERE plantId = ? 
                ORDER BY created_at DESC 
                LIMIT 1";
$verifyStmt = $conn->prepare($verifyQuery);
$verifyStmt->bind_param("i", $plantId);
$verifyStmt->execute();
$verifyResult = $verifyStmt->get_result();
$plant['lastVerification'] = $verifyResult->fetch_assoc();

// Check if plant is reserved by current user
$reserveQuery = "SELECT id, expiresAt FROM ecotrace_reservations 
                 WHERE plantId = ? AND studentId = ? AND expiresAt > NOW()";
$reserveStmt = $conn->prepare($reserveQuery);
$reserveStmt->bind_param("ii", $plantId, $user['id']);
$reserveStmt->execute();
$reserveResult = $reserveStmt->get_result();
$plant['isReservedByMe'] = $reserveResult->num_rows > 0;

Response::success($plant, 'Plant details retrieved successfully');
<?php
// Get nearby plants
require_once '../../config/db.php';
require_once '../../lib/response.php';
require_once '../../lib/auth.php';
require_once '../../lib/validators.php';
require_once '../../lib/geospatial.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Only GET requests allowed', 405);
}

// Verify authentication
$user = Auth::getCurrentUser($conn);
if (!$user) {
    Response::unauthorized();
}

// Validate parameters
if (!isset($_GET['latitude']) || !isset($_GET['longitude'])) {
    Response::validation(['message' => 'Latitude and longitude are required']);
}

$latitude = floatval($_GET['latitude']);
$longitude = floatval($_GET['longitude']);
$radius = isset($_GET['radius']) ? floatval($_GET['radius']) : 5;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if (!Validator::isValidCoordinates($latitude, $longitude)) {
    Response::validation(['message' => 'Invalid coordinates']);
}

if ($radius < 0.1 || $radius > 100) {
    Response::validation(['message' => 'Radius must be between 0.1 and 100 km']);
}

if ($limit < 1 || $limit > 100) {
    Response::validation(['message' => 'Limit must be between 1 and 100']);
}

// Get all plants
$query = "SELECT id, latitude, longitude, locationAddress, plantedDate, status 
          FROM ecotag_plants 
          LIMIT 1000";

$result = $conn->query($query);
if (!$result) {
    Response::error('Database error: ' . $conn->error, 500);
}

$plants = [];
while ($row = $result->fetch_assoc()) {
    $distance = Geospatial::calculateDistance(
        $latitude, $longitude,
        $row['latitude'], $row['longitude']
    );
    
    if ($distance <= $radius) {
        $row['distance'] = round($distance, 2);
        $plants[] = $row;
    }
}

// Sort by distance
usort($plants, function($a, $b) {
    return $a['distance'] - $b['distance'];
});

// Apply filter
$filteredPlants = [];
foreach ($plants as $plant) {
    if ($filter === 'verified' && $plant['status'] !== 'verified') continue;
    if ($filter === 'unverified' && $plant['status'] === 'verified') continue;
    $filteredPlants[] = $plant;
}

// Apply limit
$filteredPlants = array_slice($filteredPlants, 0, $limit);

Response::success(['plants' => $filteredPlants], 'Nearby plants retrieved successfully');
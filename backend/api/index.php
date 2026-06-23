<?php
// EcoTrace API Documentation

require_once '../lib/response.php';

header('Content-Type: application/json');

Response::success([
    'api_name' => 'EcoTrace Plant Monitoring API',
    'version' => '1.0.0',
    'status' => 'active',
    'endpoints' => [
        'auth' => [
            'POST /auth/login.php' => 'Login with email/password'
        ],
        'events' => [
            'GET /events/index.php' => 'List all active events',
            'GET /events/detail.php?id=1' => 'Get event details'
        ],
        'plants' => [
            'GET /plants/nearby.php?latitude=12.533&longitude=124.872&radius=5&limit=10' => 'Get nearby plants',
            'GET /plants/detail.php?id=1' => 'Get plant details'
        ],
        'reservations' => [
            'POST /reservations/create.php' => 'Reserve a plant',
            'GET /reservations/check.php?plant_id=1' => 'Check reservation status',
            'POST /reservations/release.php' => 'Release reservation'
        ],
        'verifications' => [
            'POST /verifications/submit.php' => 'Submit plant verification',
            'GET /verifications/history.php?event_id=1' => 'Get verification history'
        ]
    ],
    'authentication' => 'JWT Token in Authorization header (Bearer {token})',
    'documentation' => '../README.md'
], 'EcoTrace API v1.0.0 - Ready to use');
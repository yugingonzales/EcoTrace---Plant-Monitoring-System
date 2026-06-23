<?php
// Geospatial helper functions for distance calculations

class Geospatial {
    // Calculate distance between two coordinates (Haversine formula)
    // Returns distance in kilometers
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Radius of the earth in km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        
        return $distance;
    }
    
    // Check if a point is within radius from center
    public static function isWithinRadius($centerLat, $centerLon, $pointLat, $pointLon, $radiusKm) {
        $distance = self::calculateDistance($centerLat, $centerLon, $pointLat, $pointLon);
        return $distance <= $radiusKm;
    }
    
    // Sort plants by distance from current location
    public static function sortByDistance($plants, $userLat, $userLon) {
        usort($plants, function($a, $b) use ($userLat, $userLon) {
            $distA = Geospatial::calculateDistance(
                $userLat, $userLon,
                $a['latitude'], $a['longitude']
            );
            $distB = Geospatial::calculateDistance(
                $userLat, $userLon,
                $b['latitude'], $b['longitude']
            );
            return $distA - $distB;
        });
        return $plants;
    }
}
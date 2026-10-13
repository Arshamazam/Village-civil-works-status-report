<?php

namespace App\Services;

class GeoService
{
    /**
     * Calculate great-circle distance between two GPS coordinates in meters (Haversine formula).
     */
    public static function distanceInMeters(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 1);
    }

    /**
     * Check if coordinates fall within geofence radius.
     */
    public static function isWithinGeofence(?float $siteLat, ?float $siteLon, ?float $deviceLat, ?float $deviceLon, int $geofenceM = 500): bool
    {
        if ($siteLat === null || $siteLon === null) {
            return true; // No registered coords, permit with note
        }

        $distance = static::distanceInMeters($siteLat, $siteLon, $deviceLat, $deviceLon);
        if ($distance === null) {
            return false;
        }

        return $distance <= $geofenceM;
    }
}

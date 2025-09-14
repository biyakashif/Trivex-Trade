<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService
{
    public function fetchLocationData($ip)
    {
        $locationData = [
            'city' => 'Unknown',
            'region' => 'Unknown',
            'country' => 'Unknown',
        ];

        try {
            $response = Http::get("http://ip-api.com/json/{$ip}");
            if ($response->successful() && $response['status'] === 'success') {
                $locationData = [
                    'city' => $response['city'] ?? 'Unknown',
                    'region' => $response['regionName'] ?? 'Unknown',
                    'country' => $response['country'] ?? 'Unknown',
                ];
            } else {
                Log::warning('Failed to fetch location data for IP: ' . $ip, ['response' => $response->json()]);
            }
        } catch (\Exception $e) {
            Log::error('Error fetching location data for IP: ' . $ip, ['error' => $e->getMessage()]);
        }

        return $locationData;
    }
}
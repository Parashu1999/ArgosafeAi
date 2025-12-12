<?php
// includes/ai_prediction.php

class DiseaseForecaster {
    private $apiKey = '3373c55570a873028ebb5d50d43b98fb';
    private $city = 'Manila,PH';

    // 1. Fetch Live Weather Data
    public function getWeatherData() {
        $url = "http://api.openweathermap.org/data/2.5/weather?q={$this->city}&units=metric&appid={$this->apiKey}";
        
        // Suppress errors and use fallback data if offline
        $json = @file_get_contents($url);
        
        if ($json === FALSE) {
            // Fallback (Simulation Mode) for Localhost development
            return [
                'temp' => 31,     // Assume hot
                'humidity' => 85, // Assume humid
                'desc' => 'scattered clouds',
                'offline' => true
            ];
        }

        $data = json_decode($json, true);
        return [
            'temp' => $data['main']['temp'],
            'humidity' => $data['main']['humidity'],
            'desc' => $data['weather'][0]['description'],
            'offline' => false
        ];
    }

    // 2. The AI Prediction Logic (Decision Tree Simulation)
    public function predictRisk($weather) {
        $temp = $weather['temp'];
        $hum = $weather['humidity'];
        
        $risks = [];

        // RULE 1: Fungal Diseases (Love High Humidity + Moderate Heat)
        if ($hum > 80 && $temp > 24 && $temp < 32) {
            $risks[] = [
                'disease' => 'Rice Blast / Fungal Rot',
                'probability' => 85,
                'reason' => 'Extreme humidity (>80%) creates perfect fungal breeding ground.',
                'action' => 'Apply preventive fungicide spray immediately.'
            ];
        }

        // RULE 2: Bacterial Wilt (Loves High Heat + Moisture)
        if ($temp > 32 && $hum > 70) {
            $risks[] = [
                'disease' => 'Bacterial Wilt',
                'probability' => 75,
                'reason' => 'High heat combined with moisture triggers bacterial growth.',
                'action' => 'Ensure proper drainage and avoid nitrogen fertilizer.'
            ];
        }

        // RULE 3: Powdery Mildew (Loves Dry Heat after Rain)
        if ($hum < 60 && $temp > 28) {
            $risks[] = [
                'disease' => 'Powdery Mildew',
                'probability' => 60,
                'reason' => 'Dry, warm air favors spore dispersal.',
                'action' => 'Monitor lower leaves for white dust.'
            ];
        }

        // Default: Low Risk
        if (empty($risks)) {
            return [
                'status' => 'safe',
                'message' => 'Conditions are optimal. Low disease risk detected.',
                'color' => 'success'
            ];
        }

        // Return the highest risk
        return [
            'status' => 'danger',
            'data' => $risks[0], // Return the top match
            'color' => 'danger'
        ];
    }
}
?>
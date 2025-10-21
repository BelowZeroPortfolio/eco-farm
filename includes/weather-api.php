<?php
/**
 * Weather API Integration for Sagay, Negros Occidental
 * Using OpenWeatherMap API
 */

class WeatherAPI
{
    private $apiKey = 'YOUR_API_KEY_HERE'; // Get free API key from openweathermap.org
    private $city = 'Sagay';
    private $country = 'PH';
    private $state = 'Negros Occidental';
    private $lat = 10.8967; // Sagay City coordinates
    private $lon = 123.4167;
    private $cacheFile = 'cache/weather_cache.json';
    private $cacheTime = 1800; // 30 minutes cache

    /**
     * Get current weather data
     */
    public function getCurrentWeather()
    {
        // Check cache first
        $cachedData = $this->getCachedData();
        if ($cachedData !== null) {
            return $cachedData;
        }

        try {
            // Fetch from API
            $url = "https://api.openweathermap.org/data/2.5/weather?lat={$this->lat}&lon={$this->lon}&appid={$this->apiKey}&units=metric";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if ($data && isset($data['main'])) {
                    $weatherData = $this->formatWeatherData($data);
                    $this->cacheData($weatherData);
                    return $weatherData;
                }
            }

            // If API fails, return fallback data
            return $this->getFallbackWeather();
            
        } catch (Exception $e) {
            error_log("Weather API Error: " . $e->getMessage());
            return $this->getFallbackWeather();
        }
    }

    /**
     * Format weather data
     */
    private function formatWeatherData($data)
    {
        return [
            'temperature' => round($data['main']['temp']),
            'feels_like' => round($data['main']['feels_like']),
            'humidity' => $data['main']['humidity'],
            'pressure' => $data['main']['pressure'],
            'wind_speed' => round($data['wind']['speed'] * 3.6, 1), // Convert m/s to km/h
            'wind_direction' => $this->getWindDirection($data['wind']['deg'] ?? 0),
            'description' => ucfirst($data['weather'][0]['description'] ?? 'Clear'),
            'icon' => $this->getWeatherIcon($data['weather'][0]['id'] ?? 800),
            'rain_chance' => isset($data['rain']) ? min(100, ($data['rain']['1h'] ?? 0) * 10) : 0,
            'clouds' => $data['clouds']['all'] ?? 0,
            'visibility' => isset($data['visibility']) ? round($data['visibility'] / 1000, 1) : 10,
            'sunrise' => date('g:i A', $data['sys']['sunrise']),
            'sunset' => date('g:i A', $data['sys']['sunset']),
            'location' => 'Sagay, Negros Occidental',
            'last_updated' => date('g:i A'),
            'timestamp' => time()
        ];
    }

    /**
     * Get weather icon based on condition code
     */
    private function getWeatherIcon($code)
    {
        if ($code >= 200 && $code < 300) return 'fa-bolt'; // Thunderstorm
        if ($code >= 300 && $code < 400) return 'fa-cloud-rain'; // Drizzle
        if ($code >= 500 && $code < 600) return 'fa-cloud-showers-heavy'; // Rain
        if ($code >= 600 && $code < 700) return 'fa-snowflake'; // Snow
        if ($code >= 700 && $code < 800) return 'fa-smog'; // Atmosphere
        if ($code === 800) return 'fa-sun'; // Clear
        if ($code > 800) return 'fa-cloud'; // Clouds
        return 'fa-sun';
    }

    /**
     * Get wind direction
     */
    private function getWindDirection($degrees)
    {
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        $index = round($degrees / 45) % 8;
        return $directions[$index];
    }

    /**
     * Get cached data if valid
     */
    private function getCachedData()
    {
        $cacheDir = dirname($this->cacheFile);
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        if (file_exists($this->cacheFile)) {
            $cacheContent = file_get_contents($this->cacheFile);
            $cachedData = json_decode($cacheContent, true);
            
            if ($cachedData && isset($cachedData['timestamp'])) {
                if ((time() - $cachedData['timestamp']) < $this->cacheTime) {
                    return $cachedData;
                }
            }
        }
        
        return null;
    }

    /**
     * Cache weather data
     */
    private function cacheData($data)
    {
        $cacheDir = dirname($this->cacheFile);
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($this->cacheFile, json_encode($data));
    }

    /**
     * Fallback weather data for Sagay (typical tropical climate)
     */
    private function getFallbackWeather()
    {
        return [
            'temperature' => 28,
            'feels_like' => 32,
            'humidity' => 75,
            'pressure' => 1012,
            'wind_speed' => 12,
            'wind_direction' => 'E',
            'description' => 'Partly Cloudy',
            'icon' => 'fa-cloud-sun',
            'rain_chance' => 40,
            'clouds' => 40,
            'visibility' => 10,
            'sunrise' => '5:45 AM',
            'sunset' => '6:15 PM',
            'location' => 'Sagay, Negros Occidental',
            'last_updated' => date('g:i A'),
            'timestamp' => time()
        ];
    }
}

/**
 * Helper function to get weather data
 */
function getWeatherData()
{
    $weather = new WeatherAPI();
    return $weather->getCurrentWeather();
}

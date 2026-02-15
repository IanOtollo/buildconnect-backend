<?php
require_once __DIR__ . '/../../config/database.php';

/**
 * Gemini AI Helper
 */
class GeminiAI
{
    private $apiKey;
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct()
    {
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
    }

    public function generateResponse($prompt)
    {
        if (empty($this->apiKey)) {
            return ['error' => 'Gemini API key not configured'];
        }

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($this->apiUrl . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['error' => 'Gemini API call failed', 'details' => $response];
        }

        $result = json_decode($response, true);
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? ['error' => 'Unexpected Gemini response format'];
    }

    /**
     * Get construction project estimate
     */
    public function getProjectEstimate($description, $location)
    {
        $prompt = "As a professional construction consultant in Kenya, provide a detailed cost estimate and timeline for the following project:
        Description: $description
        Location: $location
        
        Please provide:
        1. Estimated total cost range (in KES)
        2. Break down of major costs (Materials, Labor, Permits)
        3. Estimated timeline
        4. Key considerations for this location
        
        Format the response in professional markdown.";

        return $this->generateResponse($prompt);
    }
}

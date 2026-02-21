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
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json'
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
            error_log("GEMINI_ERROR: HTTP $httpCode - Response: $response");
            return ['error' => 'Gemini API call failed', 'details' => $response];
        }

        $result = json_decode($response, true);
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            return ['error' => 'Unexpected Gemini response format'];
        }

        return $text;
    }

    /**
     * Get construction project estimate
     */
    public function getProjectEstimate($description, $location)
    {
        $prompt = "As a professional construction consultant in Kenya, provide a detailed cost estimate and timeline for the following project:
        Description: $description
        Location: $location
        
        Return ONLY a JSON object with the following structure:
        {
            \"total_cost\": \"approximate total in KES (e.g. KES 5,000,000)\",
            \"materials\": [
                {\"item\": \"material name\", \"quantity\": \"amount\", \"estimated_cost\": \"cost in KES\"}
            ],
            \"timeline\": \"estimated duration\",
            \"recommendations\": [\"list of specific advice for this location or project\"]
        }";

        return $this->generateResponse($prompt);
    }

    /**
     * Evaluate a contractor based on profile details
     */
    public function evaluateContractor($details)
    {
        $prompt = "You are an AI tasked with evaluating a contractor's suitability for a construction platform based on their profile details.
        Here are the details provided by the contractor:
        $details
        
        Determine if this contractor appears legitimate, qualified, and viable to provide services on the platform.
        Return ONLY a JSON object with the following structure:
        {
            \"status\": \"approved\" or \"rejected\",
            \"reason\": \"A brief explanation for the decision\"
        }";

        return $this->generateResponse($prompt);
    }
}

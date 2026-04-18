<?php
class GeminiService {
    private $apiKey;
    private $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent";

    public function __construct() {
        $this->apiKey = GEMINI_API_KEY;
    }

    public function generateContent($prompt, $mimeType = "text/plain") {
        $url = $this->apiUrl . "?key=" . $this->apiKey;

        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 8192,
                "responseMimeType" => $mimeType
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Prevent XAMPP cert freezes
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return ["error" => curl_error($ch)];
        }
        curl_close($ch);

        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        } else {
            return ["error" => "Invalid response from Gemini API", "details" => $result];
        }
    }

    public function generateBlogPost($topic) {
        $prompt = "Write a professional and engaging blog post about '$topic'. 
        Include:
        1. A catchy title.
        2. A short excerpt (50 words).
        3. Full content (at least 500 words) formatted in HTML. Use <h2> and <h3> for headings, and <p> for paragraphs. DO NOT wrap the whole thing in a container, just give consecutive HTML tags.
        4. Suggested categories (comma separated).
        
        Format the entire response STRICTLY as a valid JSON object with keys: title, excerpt, content, categories. Do not include markdown blocks like ```json.";
        
        $response = $this->generateContent($prompt, "application/json");
        
        // Check if the underlying generateContent failed and returned an array error
        if (is_array($response)) {
            return $response;
        }
        
        // Use regular expression to extract the outermost JSON object safely
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $response, $matches)) {
            $jsonStr = $matches[0];
        } else {
            // Fallback to just running it raw
            $jsonStr = trim($response);
            if (strpos($jsonStr, '```json') === 0) {
                $jsonStr = substr($jsonStr, 7, -3);
            } elseif (strpos($jsonStr, '```') === 0) {
                $jsonStr = substr($jsonStr, 3, -3);
            }
        }
        
        $parsed = json_decode(trim($jsonStr), true);
        if ($parsed === null) {
            return ["error" => "Failed to parse JSON from AI.", "raw_response" => $jsonStr];
        }
        
        return $parsed;
    }

    public function generateExcerpt($topic) {
        $prompt = "Write a catchy, short excerpt summarizing a blog post about '$topic'. Ensure it is strictly plain text, engaging, and absolutely LESS than 50 words. Do not use markdown or JSON.";
        $response = $this->generateContent($prompt, "text/plain");
        if (is_array($response)) return $response['error'] ?? 'Error fetching excerpt';
        return trim($response);
    }

    public function generateHtmlContent($topic) {
        $prompt = "Write a professional, comprehensive blog post about '$topic'. Format the response STRICTLY as valid consecutive HTML tags (<h2>, <h3>, <p>, <ul>). Do not wrap in a parent container, markdown code blocks, or root JSON. Ensure the content is at least 500 words and formally written.";
        $response = $this->generateContent($prompt, "text/plain");
        if (is_array($response)) return $response['error'] ?? 'Error fetching content';
        
        // Strip markdown backticks if present
        $clean = trim($response);
        if (strpos($clean, '```html') === 0) {
            $clean = substr($clean, 7, -3);
        } elseif (strpos($clean, '```') === 0) {
            $clean = substr($clean, 3, -3);
        }
        return trim($clean);
    }
}
?>

<?php
require_once 'includes/config.php';
require_once 'includes/ai_service.php';

$ai = new AIService();
$testMessage = [
    ["role" => "user", "content" => "Hello OpenAI! Reply briefly with 'ChatGPT Integration Successful!' if you can read this."]
];

echo "Testing OpenAI (ChatGPT) API Connection...\n";
$response = $ai->generateContent($testMessage);

echo "AI Response:\n";
if (is_array($response) && isset($response['error'])) {
    print_r($response);
} else {
    echo $response . "\n";
}
?>

<?php


require 'vendor/autoload.php';

require_once 'env.php';
loadEnv();

use Ollama\OllamaService;

$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
$ollamaClient = new OllamaService($apiKey);

$query = $_POST['nl_query'] ?? '';
if (empty($query)) {
    die('Missing query');
}

$response = $ollamaClient->chatCreate($query);
$responseText = $response->choices[0]->message->content ?? '';

$filters = [];

if (preg_match('/min_price:\s*(\d+)/i', $responseText, $matches)) {
    $filters['min_price'] = (int) $matches[1];
}
if (preg_match('/max_price:\s*(\d+)/i', $responseText, $matches)) {
    $filters['max_price'] = (int) $matches[1];
}
if (preg_match('/title:\s*([a-zA-Z]+)/i', $responseText, $matches)) {
    $filters['title'] = $matches[1];
}

echo "Response: $responseText\n\n";
echo "Extracted filters:\n" . print_r($filters, true);

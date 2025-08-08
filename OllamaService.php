<?php

namespace Ollama;

use GuzzleHttp\Client;

class OllamaService
{
    private $http;
    private $apiKey;
    private $apiUrl;

    public function __construct(string $apiKey)
    {
            $this->http = new Client([
        'verify' => __DIR__ . '/cert/cacert.pem' // adjust path if needed
    ]);
        $this->apiKey = $apiKey;
        $this->apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
    }

    public function chatCreate(string $query)
    {
        $payload = [
            'model' => 'deepseek/deepseek-chat', // or any free model you prefer
            'messages' => [
                [
                    'role' => 'user',
                    //'content' => "Extract filters from this product search query: '$query'. Return the result in this format exactly: min_price: <number>; max_price: <number>; title: <single word>. Do not provide any other text or explanation.",
                    'content' => "Extract any relevant filters from this product search query: '$query'. Possible filters are: min_price, max_price, title. Return only the filters that are present in this format: min_price: <number>; max_price: <number>; title: <single word>. Do not include any explanation or extra text.",

                ],
            ],
        ];

        $response = $this->http->post($this->apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => 'http://localhost/test2/', // required by OpenRouter
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return json_decode($response->getBody()->getContents());
    }
}

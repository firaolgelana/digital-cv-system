<?php
// Replace with your actual Gemini API Key
define('GEMINI_API_KEY', 'PASTE_YOUR_KEY_HERE');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the rough text sent from JavaScript
    $userInput = $_POST['raw_text'] ?? '';
    
    if (empty(trim($userInput))) {
        echo json_encode(['error' => 'No text provided.']);
        exit;
    }

    // The prompt telling the AI what to 
    $prompt = "You are an expert CV writer. Rewrite the following text to make it sound highly professional, action-oriented, and perfect for a resume. Do not add extra conversational text, just return the improved text. Here is the original text:\n\n" . $userInput;

    // Groq API Endpoint
    $url = 'https://api.groq.com/openai/v1/chat/completions';

    // OpenAI-compatible payload for Groq
    $data = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            [
                "role" => "system",
                "content" => "You are an expert CV writer. Rewrite the following text to make it sound highly professional, action-oriented, and perfect for a resume. Do not add extra conversational text, just return the improved text."
            ],
            [
                "role" => "user",
                "content" => "Here is the original text:\n\n" . $userInput
            ]
        ],
        "temperature" => 0.7
    ];

    // Vanilla PHP stream context request (No cURL extension required)
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer " . GEMINI_API_KEY . "\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true // Get response body even on HTTP error
        ]
    ];
    
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo json_encode(['error' => 'Failed to connect to AI server.']);
        exit;
    }

    $responseData = json_decode($response, true);
    
    // Check if the AI returned text (Groq/OpenAI format)
    if (isset($responseData['choices'][0]['message']['content'])) {
        $enhancedText = $responseData['choices'][0]['message']['content'];
        echo json_encode(['success' => true, 'enhanced_text' => trim($enhancedText)]);
    } else {
        $errorMessage = isset($responseData['error']['message']) ? $responseData['error']['message'] : json_encode($responseData);
        echo json_encode(['error' => 'API Issue: ' . $errorMessage]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>

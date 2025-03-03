<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Nerd\Nerdai\Classes\Models\OpenAI\gpt4;

Route::post('/api/make-prompt', function (Request $request) {
    // Retrieve JSON data from the request
    $data = $request->json()->all();

    // Process the data (example: check for a key)
    $input = $data['prompt'];

    if ($input) {
        $response = GPT4::query($input, 'prompt', 'text-generation', null);
    }

    $result = $response['result'];

    // Return a response
    return json_encode($result, JSON_UNESCAPED_UNICODE);

});


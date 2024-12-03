<?php

namespace Nerd\Nerdai\Classes\sentimentanalysis;

use Exception;
use Nerd\Nerdai\Classes\ClientFactory;
use Nerd\Nerdai\Classes\SentimentAnalysisInterface;
use Nerd\NerdAI\Models\NerdAiSettings as Settings;

class PromptQualityAnalysis implements SentimentAnalysisInterface
{

    /**
     * @inheritDoc
     */
    public function analyze(string $text)
    {
        $apiKey = Settings::get('openai_api_key');
        $organization = Settings::get('openai_api_organization');

        $client = ClientFactory::createOpenAIClient($apiKey, $organization);

        $parameters = [
            'model' => Settings::get('openai_model'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "
                    Analyze the following text and evaluate it on a scale of 1-10 for three criteria: clarity, specificity, and effectiveness. Respond only in the following format:

                    1. Clarity: X/10
                    2. Specificity: X/10
                    3. Effectiveness: X/10

                    No other information or text is needed. Ensure the response follows this format exactly.

                    Text: \"$text\"",
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0.3,
        ];

        $response = $client->query($parameters);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Sentiment analysis failed: Invalid response format.');
        }

        return trim($response['choices'][0]['message']['content']);
    }
}

<?php

namespace Nerd\Nerdai\Classes\sentimentanalysis;

use Exception;
use Nerd\Nerdai\Classes\ClientFactory;
use Nerd\Nerdai\Classes\SentimentAnalysisInterface;
use Nerd\NerdAI\Models\NerdAiSettings as Settings;

class ContentQualityAnalysis implements SentimentAnalysisInterface
{

    /**
     * @inheritDoc
     */
    public function analyze(string $text, string $prompt = '')
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
                    Evaluate the quality of the following AI response for web usage. Rate on a scale of 1-10 for each criterion: 1) Relevance to user query, 2) SEO optimization, and 3) General content quality (structure, readability, tone). Respond only in the following format:

                    1. Relevance: X/10
                    2. SEO: X/10
                    3. Quality: X/10

                    Query: \"$prompt\"

                    Response: \"$text\"
                    "
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0.3
        ];

        $response = $client->query($parameters);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Sentiment analysis failed: Invalid response format.');
        }

        return trim($response['choices'][0]['message']['content']);
    }
}

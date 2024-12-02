<?php

namespace Nerd\Nerdai\Classes\Models\OpenAI;

use Exception;
use Log;
use Nerd\Nerdai\Classes\AIModelInterface;
use Nerd\Nerdai\Classes\ClientFactory;
use Nerd\Nerdai\Classes\TaskFactory;
use Nerd\Nerdai\Classes\TaskInterface;
use Nerd\NerdAI\Models\NerdAiSettings as Settings;

class GPT4 implements AIModelInterface
{
    /**
     * Handles the entire chat completion process.
     *
     * Tasks List:
     * elaborate - Elaborate the text
     * complete - Complete the text
     * rewrite - Rewrite the text
     * summarize - Summarize the text
     * prompt - Generate a prompt for the text
     * html-code - Generate HTML code
     *
     * Mode List:
     * text-generation - Generate text from a prompt
     * More coming soon.
     *
     * @param string $prompt The chat messages array.
     * @param string $task The task to use for formatting the prompt.
     * @param string $mode The mode to use for formatting the prompt.
     * @return array The final formatted response.
     * @throws Exception
     */
    public static function query(
        string $prompt,
        string $task,
        string $mode,
    ): array {
        $taskMode = TaskFactory::resolve($task);

        $parameters = self::formatInput($prompt, $mode, $taskMode , $sentiment = '');

        $apiKey = Settings::get('openai_api_key');
        $organization = Settings::get('openai_api_organization');

        if (!$apiKey) {
            throw new Exception('OpenAI API key is missing in settings.');
        }

        // Initialize OpenAI client via ClientFactory
        $client = ClientFactory::createOpenAIClient($apiKey, $organization, $parameters);

        $rawResponse = $client->query($parameters);

        $parameters['sentiment'] = self::scorePromptQuality($prompt);
        $rawResponse['sentiment'] = self::scoreResponseRelevancy($prompt, $rawResponse['choices'][0]['message']['content']);

        $model = Settings::get('openai_model');

        $log = (new \Nerd\Nerdai\Models\Log)->logRecord($model, $task, $mode, $parameters, $rawResponse);

        return self::formatResponse($rawResponse, $log->id);
    }

    /**
     * Formats the input for a chat completion query.
     *
     * @param string $prompt The prompt to send to the OpenAI API.
     * @param TaskInterface $task The task to use for formatting the prompt.
     * @return array Formatted options for the OpenAI API.
     * @throws Exception
     */
    public static function formatInput(
        string $prompt,
        string $mode,
        TaskInterface $task,
        string $sentiment = null,
        array $parameters = []
    ): array {
        switch ($mode) {
            case 'text-generation':
                $parameters = [
                    'model' => Settings::get('openai_model'),
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $task->getResponse($prompt),
                        ]
                    ],
                    'max_tokens' => Settings::get('openai_api_max_token'),
                    'temperature' => 0.7,
                ];
                break;
            default:
                throw new Exception('Invalid mode for OpenAI API.');
        }

        return array_merge($parameters);
    }

    /**
     * Formats the response from the OpenAI API.
     *
     * @param array $response The raw response from the OpenAI API.
     * @return array The formatted response.
     * @throws Exception
     */
    public static function formatResponse(array $response, $logId, $parameters = []): array
    {
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response format from OpenAI API.');
        }

        return [
            'result' => $response['choices'][0]['message']['content'],
            'logID' => $logId
        ];
    }

    private static function scorePromptQuality(string $prompt): string
    {
        $apiKey = Settings::get('openai_api_key');
        $organization = Settings::get('openai_api_organization');

        $client = ClientFactory::createOpenAIClient($apiKey, $organization);

        $parameters = [
            'model' => Settings::get('openai_model'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Analyze the following user prompt and score its quality on a scale from 1 to 10 based on clarity, specificity, and effectiveness. Respond with only the score as a single number:\n\n\"$prompt\"",
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

    private static function scoreResponseRelevancy(string $prompt, string $response): int
    {
        $apiKey = Settings::get('openai_api_key');
        $organization = Settings::get('openai_api_organization');

        $client = ClientFactory::createOpenAIClient($apiKey, $organization);

        $parameters = [
            'model' => Settings::get('openai_model'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Evaluate the relevance of the following AI response to the given user prompt. Score it on a scale from 1 to 10 based on how well it answers the prompt. Respond with only the score as a single number:\n\nPrompt: \"$prompt\"\n\nResponse: \"$response\"",
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0.3,
        ];

        $response = $client->query($parameters);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Response relevancy scoring failed: Invalid response format.');
        }

        return (int)trim($response['choices'][0]['message']['content']);
    }
}

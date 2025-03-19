<?php

namespace Nerd\Nerdai\Classes\Models\OpenAI;

use Cache;
use Exception;
use Log;
use Nerd\Inventory\Models\Dealer;
use Nerd\Inventory\Models\Vehicle;
use Nerd\Nerdai\Classes\AIModelInterface;
use Nerd\Nerdai\Classes\ClientFactory;
use Nerd\Nerdai\Classes\services\AssistantService;
use Nerd\Nerdai\Classes\TaskFactory;
use Nerd\Nerdai\Classes\TaskInterface;
use Nerd\Nerdai\Models\Assistant;
use Nerd\NerdAI\Models\NerdAiSettings as Settings;
use Throwable;

class gpt4 implements AIModelInterface
{
    const MAX_CONCURRENT_REQUESTS = 5;
    const CACHE_DURATION = 60;

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
     * vehicle-description - Generate a vehicle description
     *
     * Mode List:
     * text-generation - Generate text from a prompt
     * assistant - Generate an assistant response
     * More coming soon.
     *
     * @param string|array $prompt The chat messages array.
     * @param string $task The task to use for formatting the prompt.
     * @param string $mode The mode to use for formatting the prompt.
     * @param Vehicle|null $vehicle
     * @return array The final formatted response.
     * @throws Throwable
     */
    public static function query(
        string|array $prompt,
        string $task,
        string $mode,
        Vehicle|null $vehicle
    ): array {
        // Handle Assistant API mode differently
        if ($mode === 'assistant-api') {
            return self::assistantQuery($prompt, $task, $vehicle);
        }
        // Handle batch processing of prompt is an array of IDs
        if (is_array($prompt)) {
            $batchId = uniqid('batch_', true);
            return self::batchQuery($prompt, $task, $mode, $batchId);
        }

        return self::singleQuery($prompt, $task, $mode, $vehicle);
    }

    /**
     * Process a query using the Assistant API
     *
     * @param string|array $prompt The input prompt or paremeters
     * @param string $task The task identifier
     * @param Vehicle|null $vehicle Optional vehicle context
     * @return array Response data
     * @throws Exception
     */
    protected static function assistantQuery(
        string|array $prompt,
        string $task,
        Vehicle|null $vehicle
    ): array {
        try {
            $assistantService = new AssistantService();

            // Get the necessary parameters
            $assistantId = null;
            $threadId = null;
            $message = '';

            if (is_array($prompt)) {
                $assistantId = $prompt['assistant_id'] ?? null;
                $threadId = $prompt['thread_id'] ?? null;
                $message = $prompt['message'] ?? '';
            } else {
                $message = $prompt;

                // If no assistant specified, get the default
                $defaultAssistant = Assistant::where('is_active', true)->first();
                if ($defaultAssistant) {
                    $assistantId = $defaultAssistant->id;
                }
            }
            if (!$assistantId) {
                throw new Exception('Assistant ID is required for assistant mode');
            }

            // Convert thread ID from string to int if needed
            if (is_string($threadId) && is_numeric($threadId)) {
                $threadId = (int) $threadId;
            }

            // Process any vehicle information
            $parameters = [];
            if ($vehicle) {
                $dealerId = $vehicle->dealer_id;
                $dealerInfo = Dealer::where('id', $dealerId)->first();

                // Add context about the vehicle/dealer
                $vehicleContext = "Vehicle: " . json_encode($vehicle->getAttributes());
                $dealerContext = "Dealer: " . json_encode($dealerInfo ? $dealerInfo->getAttributes() : null);

                $message = $vehicleContext . "\n" . $dealerContext . "\n" . $message;
            }

            // Send the conversation
            $response = $assistantService->conversation($assistantId, $message, $threadId, $parameters);

            return [
                'result' => $response['message'],
                'thread_id' => $response['thread_id'],
                'logID' => $response['logID']
            ];
        } catch (Exception $e) {
            return [
                'result' => $e->getMessage(),
                'thread_id' => null,
                'logID' => null
            ];
        }
    }

    protected static function singleQuery(
        string $prompt,
        string $task,
        string $mode,
        Vehicle|null $vehicle
    ): array {
        try {
            $taskMode = TaskFactory::resolve($task);

            $parameters = self::formatInput($prompt, $mode, $taskMode, $vehicle);

            $apiKey = Settings::get('openai_api_key');
            $organization = Settings::get('openai_api_organization');

            if (!$apiKey) {
                throw new Exception('OpenAI API key is not set.');
            }

            $client = ClientFactory::createOpenAIClient($apiKey, $organization, $parameters);
            $rawResponse = $client->query($parameters);

            $parameters['sentiment'] = self::scorePromptQuality($prompt);
            $rawResponse['sentiment'] = self::scoreResponseRelevancy($prompt, $rawResponse['choices'][0]['message']['content']);

            $model = Settings::get('openai_model');
            $log = (new \Nerd\Nerdai\Models\Log)->logRecord($model, $task, $mode, $parameters, $rawResponse);
            return self::formatResponse($rawResponse, $log->id);
        } catch (Throwable $e) {
            self::logError('Single query error', $e, ['prompt' => $prompt]);
            throw $e;
        }
    }

    protected static function batchQuery(
        array $prompts,
        string $task,
        string $mode,
        string $batchId
    ): array {
        try {
            self::initializeBatchStatus($batchId, count($prompts));

            $chunks = array_chunk($prompts, self::MAX_CONCURRENT_REQUESTS);
            $results = [];
            $logs = [];

            foreach ($chunks as $chunk) {
                // Process chunks in parallel
                $chunkResults = self::processChunkInParallel($chunk, $task, $mode, $batchId);

                // Merge results
                $results = array_merge($results, $chunkResults['results']);
                $logs = array_merge($logs, $chunkResults['logs']);

                // Update Progress
                self::updateBatchProgress($batchId, count($results));
            }

            // Finalize batch status
            self::finalizeBatchStatus($batchId, $results);

            return [
                'results' => $results,
                'logID' => $logs,
                'batchId' => $batchId,
                'status' => self::getBatchStatus($batchId)
            ];
        } catch (Throwable $e) {
            self::logError('Batch query error', $e, ['batchId' => $batchId]);
            self::updateBatchStatus($batchId, 'failed', $e->getMessage());
            throw $e;
        }
    }

    protected static function processChunkInParallel(array $chunk, string $task, string $mode, string $batchId)
    {
        $results = [];
        $logs = [];
        $promises = [];

        // Process each prompt in the chunk concurrently
        foreach ($chunk as $prompt) {
            try {
                $response = self::singleQuery($prompt, $task, $mode, null);
                $results[$prompt] = $response['result'];
                $logs[$prompt] = $response['logID'];

                self::updateBatchItemStatus($batchId, $prompt, 'completed');
            } catch (Throwable $e) {
                self::logError('Chunk processing error', $e, [
                    'prompt' => $prompt,
                    'batchId' => $batchId
                ]);

                $results[$prompt] = ['error' => $e->getMessage()];
                self::updateBatchItemStatus($batchId, $prompt, 'failed' . $e->getMessage());
            }
        }

        return [
            'results' => $results,
            'logs' => $logs
        ];
    }

    protected static function initializeBatchStatus(string $batchId, int $total): void
    {
        $status = [
            'id' => $batchId,
            'total' => $total,
            'completed' => 0,
            'failed' => 0,
            'status' => 'processing',
            'items' => [],
            'startTime' => microtime(true),
            'endTime' => null,
            'error' => null,
        ];

        Cache::put("batch_status_{$batchId}", $status, self::CACHE_DURATION);
    }

    protected static function updateBatchProgress(string $batchId, int $completed): void
    {
        $status = self::getBatchStatus($batchId);
        if ($status) {
            $status['completed'] = $completed;
            Cache::put("batch_status_{$batchId}", $status, self::CACHE_DURATION);
        }
    }

    protected static function updateBatchItemStatus(string $batchId, string $itemId, string $status, string $error = null): void
    {
        $batchStatus = self::getBatchStatus($batchId);
        if ($batchStatus) {
            $batchStatus['items'][$itemId] = [
                'status' => $status,
                'error' => $error,
                'timestap' => microtime(true)
            ];

            if ($status === 'failed') {
                $batchStatus['failed']++;
            }

            Cache::put("batch_status_{$batchId}", $batchStatus, self::CACHE_DURATION);
        }
    }

    protected static function finalizeBatchStatus(string $batchId, array $results): void
    {
        $status = self::getBatchStatus($batchId);
        if ($status) {
            $status['status'] = 'completed';
            $status['endTime'] = microtime(true);
            $status['duration'] = $status['endTime'] - $status['startTime'];
            Cache::put("batch_status_{$batchId}", $status, self::CACHE_DURATION);
        }
    }

    public static function getBatchStatus(string $batchId): ?array
    {
        return Cache::get("batch_status_{$batchId}");
    }

    protected static function updateBatchStatus(string $batchId, string $status, string $error = null): void
    {
        $batchStatus = self::getBatchStatus($batchId);
        if ($batchStatus) {
            $batchStatus['status'] = $status;
            $batchStatus['error'] = $error;
            $batchStatus['endTime'] = microtime(true);
            Cache::put("batch_status_{$batchId}", $batchStatus, self::CACHE_DURATION);
        }
    }

    protected static function logError(string $message, Throwable $exception, array $context = []): void
    {
        Log::error($message, [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context
        ]);
    }

    /**
     * Formats the input for a chat completion query.
     *
     * @param string $prompt The prompt to send to the OpenAI API.
     * @param TaskInterface $task The task to use for formatting the prompt.
     * @param string | null $sentiment The sentiment analysis to give the AI for self-enhancing capability.
     * @param array $parameters An optional argument to add any custom parameters to the Input.
     * @return array Formatted options for the OpenAI API.
     * @throws Exception
     */
    public static function formatInput(
        string $prompt,
        string $mode,
        TaskInterface $task,
        Vehicle|null $vehicle,
        array $parameters = []
    ): array {
        if ($vehicle) {
            $dealerId = $vehicle->dealer_id;
            $dealerInfo = Dealer::where('id', $dealerId)->first();
        } else {
            $dealerInfo = Dealer::where('is_default' , 1)->first();
        }
        switch ($mode) {
            case 'text-generation':
                $options = [
                    'dealerInfo' => $dealerInfo,
                    'vehicleInfo' => $vehicle
                ];
                $parameters = [
                    'model' => Settings::get('openai_model'),
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $task->getResponse($prompt, $options),
                        ]
                    ],
                    'max_tokens' => Settings::get('openai_api_max_token'),
                    'temperature' => 0.7,
                ];
                break;
            case 'vision-analysis':
                $parameters = [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => is_array($prompt) ? ($prompt['prompt'] ?? 'Analyze this image.') : $prompt
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => is_array($prompt) ? $prompt['image'] : $prompt,
                                        'detail' => 'auto'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => Settings::get('openai_api_max_token', 300)
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
                    'content' => "Analysez le texte suivant et évaluez-le sur une échelle de 1 à 10 selon trois critères : clarté, spécificité et efficacité. Répondez uniquement dans le format suivant :\n\n1. Clarté: X/10\n2. Spécificité: X/10\n3. Efficacité: X/10\n\nAucune autre information ou texte n'est nécessaire. Assurez-vous que la réponse suit exactement ce format.\n\nTexte : \"$prompt\"",
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

    private static function scoreResponseRelevancy(string $prompt, string $response): string
    {
        $apiKey = Settings::get('openai_api_key');
        $organization = Settings::get('openai_api_organization');

        $client = ClientFactory::createOpenAIClient($apiKey, $organization);

        $parameters = [
            'model' => Settings::get('openai_model'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Évaluez la qualité de la réponse suivante de l'IA basé sur la requête pour une utilisation sur le web. Donnez une note sur une échelle de 1 à 10 pour chaque critère suivant : 1) Pertinence par rapport au texte de l'utilisateur, 2) Optimisation pour le référencement naturel (SEO), et 3) Qualité générale du contenu (structure, lisibilité, ton). Répondez uniquement dans le format suivant :\n\n1. Pertinence: X/10\n2. SEO: X/10\n3. Qualité générale: X/10\n\nRequête : \"$prompt\"\n\nRéponse : \"$response\"",
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0.3,
        ];

        $response = $client->query($parameters);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Response relevancy scoring failed: Invalid response format.');
        }

        return $response['choices'][0]['message']['content'];
    }
}

<?php

namespace Nerd\Nerdai\Classes\Models\OpenAI;

use Nerd\Nerdai\Classes\AssistantInterface;
use Cache;
use Exception;
use Log;
use Nerd\Nerdai\Classes\ClientFactory;
use Nerd\Nerdai\Models\NerdAiSettings as Settings;
use Throwable;

class OpenAIAssistant implements AssistantInterface
{

    const CACHE_DURATION = 60; // minutes
    const DEFAULT_POLL_INTERVAL = 2; // seconds
    const DEFAULT_TIMEOUT = 120; // seconds

    protected $client;

    public function __construct()
    {
        $apiKey = Settings::get('openai_api_key');
        $organization = Settings::get('openai_api_organization');

        if (!$apiKey) {
            throw new Exception('OpenAI API key is not set.');
        }

        $this->client = ClientFactory::createOpenAIClient($apiKey, $organization);
    }

    /**
     * @inheritDoc
     */
    public function createAssistant(string $name, string $instructions, array $tools = [], array $parameters = []): array
    {
        try {
            $model = $parameters['model'] ?? Settings::get('openai_model', 'gpt-4o');

            return $this->client->createAssistant(
                $name,
                $instructions,
                $model,
                $tools
            );
        } catch (Throwable $e) {
            $this->logError('Failed to create asistant', $e, [
                'name' => $name,
                'instructions' => substr($instructions,0 , 100) . '...'
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve an assistant by ID
     *
     * @param string $assistantId Assistant ID
     * @return array Assistant data
     */
    public function getAssistant(string $assistantId): array
    {
        try {
            return $this->client->getAssistant($assistantId);
        } catch (Throwable $e) {
            $this->logError('Failed to retrieve assistant', $e, [
                'assistant_id' => $assistantId
            ]);
            throw $e;
        }
    }

    /**
     * Update an assistant
     *
     * @param string $assistantId Assistant ID
     * @param array $data Update data
     * @return array Updated assistant data
     */
    public function updateAssistant(string $assistantId, array $data): array
    {
        try {
            return $this->client->updateAssistant($assistantId, $data);
        } catch (Throwable $e) {
            $this->logError('Failed to update assistant', $e, [
                'assistant_id' => $assistantId,
                'update_data' => json_encode($data)
            ]);
            throw $e;
        }
    }

    /**
     * List all assistants
     *
     * @param array $parameters Optional filtering parameters
     * @return array Asssitants list
     */
    public function listAssistants(array $parameters = []): array
    {
        try {
            return $this->client->listAssistants($parameters);
        } catch (Throwable $e) {
            $this->logError('Failed to list assistants', $e);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function createThread(array $initialMessages = []): array
    {
        try {
            return $this->client->createThread($initialMessages);
        } catch (Throwable $e) {
            $this->logError('Failed to create thread', $e);
            throw $e;
        }
    }

    /**
     * Retrieve a thread
     *
     * @param string $threadId Thread ID
     * @return array Thread data
     */
    public function getThread(string $threadId): array
    {
        try {
            return $this->client->getThread($threadId);
        } catch (Throwable $e) {
            $this->logError('Failed to retrieve thread', $e, [
                'thread_id' => $threadId
            ]);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function addMessage(string $threadId, string $content, array $attachments = []): array
    {
        try {
            return $this->client->addMessage($threadId, 'user', $content, $attachments);
        } catch (Throwable $e) {
            $this->logError('Failed to add message to thread', $e, [
                'thread_id' => $threadId,
                'content_preview' => $content
            ]);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function runAssistant(string $threadId, string $assistantId, array $parameters = []): array
    {
        try {
            return $this->client->createRun($threadId, $assistantId, $parameters);

            $runId = $run['id'];

            // Wait for the run to complete
            $timeout = $parameters['timeout'] ?? self::DEFAULT_TIMEOUT;
            $pollInterval = $parameters['poll_interval'] ?? self::DEFAULT_POLL_INTERVAL;

            $completedRun = $this->client->waitForRun($theadId, $runId, $timeout, $pollInterval);

            // Log the run
            $model = Settings::get('openai_model');
            $log = (new \Nerd\Nerdai\Models\Log)->logRecord(
                $model,
                'assistant-run',
                'assistant-api',
                [
                    'thread_id' => $threadId,
                    'assistant_id' => $assistantId,
                    'run_id' => $runId
                ],
                [
                    'status' => $completedRun['status'],
                    'completed_at' => $completedRun['completed_at'] ?? null,
                ]
            );

            // If the run completed successfully, retrieve the messages
            if ($completedRun['status'] === 'completed') {
                $messages = $this->getThreadMessages($threadId, [
                    'order' => 'desc',
                    'limit' => 1
                ]);

                return [
                    'run' => $completedRun,
                    'messages' => $messages,
                    'logId' => $log->id
                ];
            }

            throw new Exception("Run didn't complete successfully. Status: " . $completedRun['status']);
        } catch (Throwable $e) {
            $this->logError('Run assistant error', $e, [
                'thread_id' => $threadId,
                'assistant_id' => $assistantId
            ]);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getThreadMessages(string $threadId, array $parameters = []): array
    {
        try {
            return $this->client->listmessages($threadId, $parameters);
        } catch (Throwable $e) {
            $this->logError('Failed to list messages in thread', $e, [
                'thread_id' => $threadId
            ]);
            throw $e;
        }
    }

    /**
     * Run a conversationnal assistant with a single prompt
     *
     * @param string $assistantId Assistant ID
     * @param string $prompt User prompt
     * @param string $threadId Optional thread ID for continued conversation
     * @param array $parameters Additional parameters
     * @return array Response with message content
     */
    public function conversation(string $assistantId, string $prompt, ?string $threadId = null, array $parameters = []): array
    {
        try {
            // Create or retrieve thread
            if ($threadId) {
                try {
                    $thread = $this->getThread($threadId);
                } catch (Exception $e) {
                    // If thread doesn't exist or error, create a new one
                    $thread = $this->createThread();
                    $threadId = $thread['id'];
                }
            } else {
                $thread = $this->createThread();
                $threadId = $thread['id'];
            }

            $this->addMessage($threadId, $prompt);

            $result = $this->runAssistant($threadId, $assistantId, $parameters);

            $response = '';
            if (isset($result['messagse']['data'][0]['content'][0]['text']['value'])) {
                $response = $result['messages']['data'][0]['content'][0]['text']['value'];
            }

            return [
                'result' => $response,
                'thread_id' => $threadId,
                'logID' => $result['logId']
            ];
        } catch (Throwable $e) {
            $this->logError('Conversation error', $e, [
                'assistant_id' => $assistantId,
                'prompt_review' => substr($prompt, 0, 100) . '...'
            ]);
            throw $e;
        }
    }

    /**
     * Log errors
     *
     * @param string $message Error message
     * @param Throwable $exception Exception
     * @param array $context Context
     */
    protected function logError(string $message, Throwable $exception, array $context = []): void
    {
        Log::error($message, [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context
        ]);
    }
}

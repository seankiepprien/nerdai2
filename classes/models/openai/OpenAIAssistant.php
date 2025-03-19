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
                'instructions' => substr($instructions, 0, 100) . '...'
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
     * @return array Assistants list
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
            // Get the assistant
            $assistant = Assistant::where('assistant_id', $assistantId)->firstOrFail();

            // Create a run on the thread with the assistant
            $run = $this->client->createRun($threadId, $assistantId, $parameters);
            $runId = $run['id'];

            // Wait for the run to complete or require action
            $timeout = $parameters['timeout'] ?? 120;
            $pollInterval = $parameters['poll_interval'] ?? 2;

            // Loop until run is completed or fails
            $startTime = time();
            while (true) {
                $runStatus = $this->client->getRun($threadId, $runId);

                // Check if timeout reached
                if (time() - $startTime > $timeout) {
                    throw new Exception("Run timed out after {$timeout} seconds");
                }

                // Process function calls if needed
                if ($runStatus['status'] === 'requires_action' &&
                    isset($runStatus['required_action']['type']) &&
                    $runStatus['required_action']['type'] === 'submit_tool_outputs') {

                    $this->processFunctionCalls($threadId, $runId, $assistant);

                    // Continue polling after submitting tool outputs
                    sleep($pollInterval);
                    continue;
                }

                // Check if run is completed or failed
                if (in_array($runStatus['status'], ['completed', 'failed', 'cancelled', 'expired'])) {
                    break;
                }

                // Wait before checking again
                sleep($pollInterval);
            }

            // Check if the run completed successfully
            if ($runStatus['status'] !== 'completed') {
                throw new Exception("Run failed with status: " . $runStatus['status']);
            }

            // Get the latest messages from the thread
            $messages = $this->client->listMessages($threadId, [
                'order' => 'desc',
                'limit' => 1
            ]);

            // Return the response
            return [
                'run' => $runStatus,
                'messages' => $messages,
                'assistant_id' => $assistantId
            ];
        } catch (Exception $e) {
            \Log::error('Error running assistant', [
                'error' => $e->getMessage(),
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
     * Run a conversational assistant with a single prompt
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

    public function deleteAssistant(string $assistantId)
    {
        try {
            $this->client->deleteAssistant($assistantId);
        } catch (Throwable $e) {
            $this->logError('Failed to delete assistant', $e, [
                'assistant_id' => $assistantId
            ]);
            throw $e;
        }
    }

    /**
     * Get response from an assistant after adding a message to a thread
     *
     * @param string $threadId OpenAI thread ID
     * @param string $assistantId OpenAI assistant ID
     * @param array $parameters Additional parameters
     * @return array Response data
     */
    public function getResponse(string $threadId, string $assistantId, array $parameters = []): array
    {
        try {
            // Get response using the client
            $parameters['assistant_id'] = $assistantId;

            $response = $this->client->getAssistantResponse($threadId, $parameters);

            // Process and format the response
            $result = [
                'success' => true,
                'message' => $this->extractMessageContent($response['messages']),
                'assistant_id' => $assistantId,
                'thread_id' => $threadId
            ];

            return $result;
        } catch (Exception $e) {
            Log::error('Error getting assistant response: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract message content from the messages response
     *
     * @param array $messages Messages from the API
     * @return string The extracted message content
     */
    private function extractMessageContent(array $messages): string
    {
        // Get the first message (should be the assistant's response)
        if (!isset($messages['data']) || empty($messages['data'])) {
            return '';
        }

        $message = $messages['data'][0];

        // Check if it's an assistant message
        if ($message['role'] !== 'assistant') {
            return '';
        }

        // Extract the content
        if (isset($message['content'][0]['text']['value'])) {
            return $message['content'][0]['text']['value'];
        }

        return '';
    }

    /**
     * Process a run that requires function calling
     *
     * @param string $threadId Thread ID
     * @param string $runId Run ID
     * @param Assistant $assistant Assistant model
     * @return array Result of function execution
     */
    protected function processFunctionCalls(string $threadId, string $runId, Assistant $assistant)
    {
        // Get required actions from the run
        $requiredAction = $this->client->getRequiredAction($threadId, $runId);

        if (!isset($requiredAction['submit_tool_outputs'])) {
            throw new Exception('Run does not require tool outputs');
        }

        $toolCalls = $requiredAction['submit_tool_outputs']['tool_calls'] ?? [];
        $toolOutputs = [];

        // Get function handler for this assistant
        $handler = $this->assistantService->getFunctionHandler($assistant);

        if (!$handler) {
            throw new Exception('No function handler found for assistant ' . $assistant->name);
        }

        // Process each tool call
        foreach ($toolCalls as $toolCall) {
            // Only process function calls
            if ($toolCall['type'] !== 'function') {
                continue;
            }

            $functionName = $toolCall['function']['name'] ?? '';
            $functionArguments = json_decode($toolCall['function']['arguments'] ?? '{}', true);

            // Log the function call
            \Log::info('Processing function call', [
                'assistant' => $assistant->name,
                'function' => $functionName,
                'arguments' => $functionArguments
            ]);

            try {
                // Call the function handler
                $output = $handler->processFunction($functionName, $functionArguments, $threadId);

                $toolOutputs[] = [
                    'tool_call_id' => $toolCall['id'],
                    'output' => json_encode($output)
                ];
            } catch (Exception $e) {
                // Log error and provide error message as output
                \Log::error('Function call error', [
                    'function' => $functionName,
                    'error' => $e->getMessage()
                ]);

                $toolOutputs[] = [
                    'tool_call_id' => $toolCall['id'],
                    'output' => json_encode(['error' => $e->getMessage()])
                ];
            }
        }

        // Submit the tool outputs
        return $this->client->submitToolOutputs($threadId, $runId, $toolOutputs);
    }
}

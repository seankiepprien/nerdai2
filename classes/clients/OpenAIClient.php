<?php namespace Nerd\Nerdai\Classes\Clients;

use Exception;
use Nerd\Nerdai\Classes\ClientInterface;
use OpenAI;

/**
 * Class OpenAIClient
 *
 * Wrapper class for interacting with the OpenAI API, including Assistants.
 *
 * @package Nerd\Nerdai\classes
 */
class OpenAIClient implements ClientInterface
{
    protected $client;
    protected string $apiKey;
    protected ?string $organization = null;
    protected array $headers = ['Openai-Beta', 'assistants=v2'];
    protected array $parameters = [];
    protected string $model = 'gpt-3.5-turbo-instruct';

    public function __construct(string $apiKey, string $organization = null, array $parameters = [])
    {
        $this->setApiKey($apiKey);
        $this->setOrganization($organization);

        if (isset($parameters['model'])) {
            $this->setModel($parameters['model']);
        }

        if (isset($parameters['headers'])) {
            $this->setHttpHeaders($parameters['headers']);
        }

        if (isset($parameters['parameters'])) {
            $this->setParameters($parameters['parameters']);
        }

        $this->client = $this->makeClient();
    }

    public function setApiKey(string $key): void
    {
        $this->apiKey = $key;
    }

    public function setOrganization(?string $organization): void
    {
        $this->organization = $organization;
    }

    public function setHttpHeaders(array $headers): void
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function makeClient(): object
    {
        $client = OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withOrganization($this->organization)
            ->withHttpHeader(...$this->headers)
            ->make();

        return $client;
    }

    /**
     * Standard chat completion query
     *
     * @param array $options Chat completion options
     * @return array Response from the API
     * @throws Exception
     */
    public function query(array $options): array
    {
        try {
            $response = $this->client->chat()->create($options);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('OpenAI API Query failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a new assistant
     *
     * @param string $name Assistant name
     * @param string $instructions Instructions for the assistant
     * @param string $model Model to use for the assistant
     * @param array $tools Tools to enable for the assistant
     * @return array Assistant data
     * @throws Exception
     */
    public function createAssistant(string $name, string $instructions, string $model, array $tools = []): array
    {
        try {
            $response = $this->client->assistants()->create([
                'name' => $name,
                'instructions' => $instructions,
                'model' => $model,
                'tools' => $tools
            ]);

            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to create assistant: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve an assistant by ID
     *
     * @param string $assistantId The ID of the assistant to retrieve
     * @return array Assistant data
     * @throws Exception
     */
    public function getAssistant(string $assistantId): array
    {
        try {
            $response = $this->client->assistants()->retrieve($assistantId);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve assistant: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing assistant
     *
     * @param string $assistantId The ID of the assistant to update
     * @param array $data The data to update
     * @return array Updated assistant data
     * @throws Exception
     */
    public function updateAssistant(string $assistantId, array $data): array
    {
        try {
            $response = $this->client->assistants()->update($assistantId, $data);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to update assistant: ' . $e->getMessage());
        }
    }

    /**
     * Delete an assistant
     *
     * @param string $assistantId The ID of the assistant to delete
     * @return array Response data
     * @throws Exception
     */
    public function deleteAssistant(string $assistantId): array
    {
        try {
            $response = $this->client->assistants()->delete($assistantId);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to delete assistant: ' . $e->getMessage());
        }
    }

    /**
     * List all assistants
     *
     * @param array $params Optional parameters like limit, order, etc.
     * @return array List of assistants
     * @throws Exception
     */
    public function listAssistants(array $params = []): array
    {
        try {
            $response = $this->client->assistants()->list($params);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to list assistants: ' . $e->getMessage());
        }
    }

    /**
     * Create a new thread
     *
     * @param array $messages Optional initial messages for the thread
     * @return array Thread data
     * @throws Exception
     */
    public function createThread(array $messages = []): array
    {
        try {
            $params = [];
            if (!empty($messages)) {
                $params['messages'] = $messages;
            }

            $response = $this->client->threads()->create($params);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to create thread: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a thread by ID
     *
     * @param string $threadId The ID of the thread to retrieve
     * @return array Thread data
     * @throws Exception
     */
    public function getThread(string $threadId): array
    {
        try {
            $response = $this->client->threads()->retrieve($threadId);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve thread: ' . $e->getMessage());
        }
    }

    /**
     * Delete a thread
     *
     * @param string $threadId The ID of the thread to delete
     * @return array Response data
     * @throws Exception
     */
    public function deleteThread(string $threadId): array
    {
        try {
            $response = $this->client->threads()->delete($threadId);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to delete thread: ' . $e->getMessage());
        }
    }

    /**
     * Add a message to a thread
     *
     * @param string $threadId The ID of the thread
     * @param string $role The role of the message sender (user)
     * @param string $content The content of the message
     * @param array $attachments Optional file attachments
     * @return array Message data
     * @throws Exception
     */
    public function addMessage(string $threadId, string $role, string $content, array $attachments = []): array
    {
        try {
            $params = [
                'role' => $role,
                'content' => $content
            ];

            if (!empty($attachments)) {
                $params['attachments'] = $attachments;
            }

            $response = $this->client->threads()->messages()->create($threadId, $params);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to add message to thread: ' . $e->getMessage());
        }
    }

    /**
     * List messages in a thread
     *
     * @param string $threadId The ID of the thread
     * @param array $params Optional parameters like limit, order, etc.
     * @return array List of messages
     * @throws Exception
     */
    public function listMessages(string $threadId, array $params = []): array
    {
        try {
            $response = $this->client->threads()->messages()->list($threadId, $params);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to list messages: ' . $e->getMessage());
        }
    }

    /**
     * Get response from an assistant after adding a message to a thread
     *
     * @param string $threadId The thread ID
     * @param array $parameters Additional parameters
     * @return array Response data
     * @throws Exception
     */
    public function getAssistantResponse(string $threadId, array $parameters = []): array
    {
        try {
            $assistantId = $parameters['assistant_id'] ?? null;

            if (!$assistantId) {
                throw new Exception('Assistant ID is required');
            }

            $run = $this->createRun($threadId, $assistantId, $parameters);

            $runId = $run['id'];

            $timeout = $parameters['timeout'] ?? 120;
            $pollInterval = $parameters['poll_interval'] ?? 2;

            $completedRun = $this->waitForRun($threadId, $runId, $timeout, $pollInterval);

            if ($completedRun['status'] !== 'completed') {
                throw new Exception('Run failted with status: ' . $completedRun['status']);
            }

            $messages = $this->listMessages($threadId, [
                'order' => 'desc',
                'limit' => 1
            ]);

            return [
                'run' => $completedRun,
                'messages' => $messages,
                'assistant_id' => $assistantId
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to get assistant response: ' . $e->getMessage());
        }
    }

    /**
     * Create a run (execute the assistant on a thread)
     *
     * @param string $threadId The ID of the thread
     * @param string $assistantId The ID of the assistant
     * @param array $params Optional parameters
     * @return array Run data
     * @throws Exception
     */
    public function createRun(string $threadId, string $assistantId, array $params = []): array
    {
        try {
            $runParams = array_merge([
                'assistant_id' => $assistantId
            ], $params);

            $response = $this->client->threads()->runs()->create($threadId, $runParams);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to create run: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a run
     *
     * @param string $threadId The ID of the thread
     * @param string $runId The ID of the run
     * @return array Run data
     * @throws Exception
     */
    public function getRun(string $threadId, string $runId): array
    {
        try {
            $response = $this->client->threads()->runs()->retrieve($threadId, $runId);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve run: ' . $e->getMessage());
        }
    }

    /**
     * Wait for a run to complete
     *
     * @param string $threadId The ID of the thread
     * @param string $runId The ID of the run
     * @param int $timeout Maximum time to wait in seconds
     * @param int $pollingInterval Time between polling attempts in seconds
     * @return array Completed run data
     * @throws Exception
     */
    public function waitForRun(string $threadId, string $runId, int $timeout = 300, int $pollingInterval = 2): array
    {
        $startTime = time();
        $completedStatuses = ['completed', 'failed', 'cancelled', 'expired'];

        while (true) {
            $run = $this->getRun($threadId, $runId);

            if (in_array($run['status'], $completedStatuses)) {
                return $run;
            }

            if (time() - $startTime > $timeout) {
                throw new Exception("Run did not complete within timeout period of {$timeout} seconds");
            }

            sleep($pollingInterval);
        }
    }

    /**
     * Process an image with GPT-4 Vision
     *
     * @param array $messages Array containing the message structure with text and image
     * @param array $options Additional options for the API call
     * @return array Response from the API
     * @throws Exception
     */
    public function vision(array $messages, array $options = []): array
    {
        $parameters = array_merge([
            'model' => 'gpt-4-vision-preview',
            'messages' => $messages,
            'max_tokens' => 500,
        ], $options);

        try {
            $response = $this->client->chat()->create($parameters);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Vision API Query Failed: ' . $e->getMessage());
        }
    }

    /**
     * Convert an uploaded file to a base64 data URL
     *
     * @param string $filePath Path to the image file
     * @return string Base64 encoded data URL
     * @throws Exception
     */
    public function imageToDataUrl(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new Exception('Image file not found: ' . $filePath);
        }

        $type = mime_content_type($filePath);
        if (!str_starts_with($type, 'image/')) {
            throw new Exception('File is not an image: ' . $type);
        }

        $data = base64_encode(file_get_contents($filePath));
        return "data:$type;base64,$data";
    }

    /**
     * Get required actions for a run
     *
     * @param string $threadId Thread ID
     * @param string $runId Run ID
     * @return array Required action data
     */
    public function getRequiredAction(string $threadId, string $runId): array
    {
        $run = $this->getRun($threadId, $runId);

        if ($run['status'] !== 'requires_action') {
            return [];
        }

        return $run['required_action'] ?? [];
    }

    /**
     * Submit tool outputs for a run
     *
     * @param string $threadId Thread ID
     * @param string $runId Run ID
     * @param array $toolOutputs Tool outputs
     * @return array Updated run data
     */
    public function submitToolOutputs(string $threadId, string $runId, array $toolOutputs): array
    {
        try {
            $response = $this->client->threads()->runs()->submitToolOutputs(
                $threadId,
                $runId,
                ['tool_outputs' => $toolOutputs]
            );

            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to submit tool outputs: ' . $e->getMessage());
        }
    }
}

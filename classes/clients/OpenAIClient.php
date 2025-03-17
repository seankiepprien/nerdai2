<?php namespace Nerd\Nerdai\Classes\Clients;

use Exception;
use Nerd\Nerdai\Classes\ClientInterface;
use OpenAI;

/**
 * Class Client
 *
 * Wrapper class for interacting with the OpenAI API.
 *
 * @package Nerd\Nerdai\classes
 */
class OpenAIClient implements ClientInterface
{
    protected $client;
    protected string $apiKey;
    protected ?string $organization = null;
    protected array $headers = ['Openai-Beta', 'assistant=v2'];
    protected array $parameters = [];
    protected string $model = 'gpt-4o';
    protected ?string $assistantId = null;

    public function __construct(string $apiKey, string $organization = null, array $parameters)
    {
        $this->setApiKey($apiKey);
        $this->setOrganization($organization);
        if (array_key_exists('model', $parameters)) {
            $this->setModel($parameters['model']);
        }

        if (array_key_exists('headers', $parameters)) {
            $this->setHttpHeaders($parameters['headers']);
        }

        if (array_key_exists('parameters', $parameters)) {
            $this->setParameters($parameters['parameters']);
        }

        if (array_key_exists('assistantId', $parameters)) {
            $this->assistantId = $parameters['assistantId'];
        }

        $this->client = $this->makeClient();
    }

    public function setApiKey(string $key): void
    {
        $this->apiKey = $key;
    }

    public function setOrganization(string $organization): void
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

    public function setAssistantId(?string $assistantId): void
    {
        $this->assistantId = $assistantId;
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

    public function query(array $options): array
    {
        if(isset($options['assistant_mode']) && $options['assistant_mode'] === true) {
            return $this->assistantQuery($options);
        }

        try {
            $response = $this->client->chat()->create($options);

            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('OpenAI API Query failed: ' . $e->getMessage());
        }
    }

    /**
     * Query the Assistant API
     *
     * @param array $options Parameters for the Assistant API
     * @return array Response from the API
     * @throws Exception
     */
    public function assistantQuery(array $options): array
    {
        $threadId = $options['thread_id'] ?? null;
        $userMessage = $options['message'] ?? null;
        $assistantId = $options['assistant_id'] ?? $this->assistantId;

        if (!$userMessage) {
            throw new Exception('No message provided for Assistant API');
        }

        if (!$assistantId) {
            throw new Exception('No assistant ID provided for Assistant API');
        }

        try {
            // Create new thread if none is provided
            if (!$threadId) {
                $thread = $this->createThread();
                $threadId = $thread['id'];
            }

            // Add the user message to the thread
            $this->addMessage($threadId, $userMessage);

            // Run the Assistant
            $run = $this->runAssistant($threadId, $assistantId);

            // Run for the run to complete
            $runStatus = $this->waitForRun($threadId, $run['id']);

            // Get the messages
            $messages = $this->getMessages($threadId, ['order' => 'desc', 'limit' => 1]);

            return [
                'thread_id' => $threadId,
                'run' => $runStatus,
                'messages' => $messages,
                'response' => $messages['data'][0]['content'][0]['text']['value'] ?? null
            ];
        } catch (Exception $e) {
            throw new Exception('Assistant API Query failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a new thread
     *
     *  @return array The response from the API
     *  @throws Exception
     * */
    public function createThread(): array
    {
        try {
            $response = $this->client->threads()->create();
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Thread creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Run an assistant
     *
     * @param string $threadId The ID of the thread to run the assistant on
     * @param string $assistantId The ID of the assistant to run
     * @return array The response from the API
     * @throws Exception
     */
    public function runAssistant(string $threadId, string $assistantId): array
    {
        try {
            $response = $this->client->threads()->runs()->create($threadId, [
                'assistant_id' => $assistantId
            ]);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to run assistant: ' . $e->getMessage());
        }
    }

    /**
     * Wait for an assistant run to complete
     *
     * @param string $threadId The ID of the thread to wait for
     * @param string $runId The ID of the run to wait for
     * @param int $timeout The timeout in seconds
     * @return array The response from the API
     * @throws Exception
     */
    public function waitForRun(string $threadId, string $runId, int $timeout = 30): array
    {
        $startTime = time();

        while ((time() - $startTime) < $timeout) {
            try {
                $runStatus = $this->client->threads()->runs()->retrieve($threadId, $runId)->toArray();

                if ($runStatus['status'] === 'completed') {
                    return $runStatus;
                }

                sleep(2);
            } catch (Exception $e) {
                throw new Exception('Failed to get run status: ' . $e->getMessage());
            }
        }

        throw new Exception('Timeout waiting for assistant run to complete.');
    }

    /**
     * Get messages from a thread
     *
     * @param string $threadId The ID of the thread to get messages from
     * @param array $options The options to pass to the API
     * @return array The response from the API
     * @throws Exception
     */
    public function getMessages(string $threadId, array $options = []): array
    {
        try {
            return $this->client->threads()->messages()->list($threadId, $options)->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to get messages: ' . $e->getMessage());
        }
    }

    /**
     * Add a message to a thread
     *
     * @param string $threadId The ID of the thread to add the message to
     * @param string $message The message to add to the thread
     * @return array The response from the API
     * @throws Exception
     */
    public function addMessage(string $threadId, string $message): array
    {
        try {
            $response = $this->client->threads()->messages()->create($threadId, [
                'role' => 'user',
                'content' => $message
            ]);
            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Message addition failed: ' . $e->getMessage());
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
}

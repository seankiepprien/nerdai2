<?php

namespace Nerd\Nerdai\Classes\services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Nerd\Nerdai\Classes\Models\OpenAI\OpenAIAssistant;
use Nerd\Nerdai\Models\Assistant;
use Nerd\Nerdai\Models\Thread;
use Nerd\Nerdai\Models\Message;
use Nerd\Nerdai\Models\NerdAiSettings as Settings;

class AssistantService
{
    protected OpenAIAssistant $api;

    public function __construct()
    {
        $this->api = new OpenAIAssistant;
    }

    /**
     * Create a new Assistant
     *
     * @param string $name Name of the assistant
     * @param string $instructions Instructions for the assistant
     * @param string $description Optional description
     * @param array $tools Optional tools to enable
     * @param array $parameters Additional Parameters
     * @return Assistant
     */
    public function createAssistant(string $name, string $instructions, string $description = '', array $tools = [], array $parameters = []): Assistant
    {
        $response = $this->api->createAssistant($name, $instructions, $tools, $parameters);
        $assistant = new Assistant();
        $assistant->name = $name;
        $assistant->assistant_id = $response['id'];
        $assistant->description = $description;
        $assistant->instructions = $instructions;
        $assistant->model = $response['model'];
        $assistant->tools = $tools;
        $assistant->save();

        return $assistant;
    }

    /**
     * Update an assistant
     *
     * @param int $id Local assistant ID
     * @param array $data Update data
     * @return Assistant
     */
    public function updateAssistant(int $id, array $data): Assistant
    {
        $assistant  = Assistant::findOrFail($id);

        $apiData = [];

        if (isset($data['name'])) $apiData['name'] = $data['name'];
        if (isset($data['instructions'])) $apiData['instructions'] = $data['instructions'];
        if (isset($data['tools'])) $apiData['tools'] = $data['tools'];
        if (isset($data['model'])) $apiData['model'] = $data['model'];

        if (!empty($emptyData)) {
            $this->api->updateAssistant($assistant->assistant_id, $emptyData);
        }

        $assistant->fill($data);
        $assistant->save();

        return $assistant;
    }

    /**
     * Get all assistants
     *
     * @param bool $onlyActive Whether to retrieve only active assistants
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssistants(bool $onlyActive = true): Collection
    {
        if ($onlyActive) {
            return Assistant::where('is_active')->get();
        }

        return Assistant::all();
    }

    /**
     * Get a single assistant
     *
     * @param int $id Assistant ID
     * @return Assistant
     */
    public function getAssistant(int $id): Assistant
    {
        return Assistant::findOrFail($id);
    }

    /**
     * Create a new thread of an assistant
     *
     * @param int $assistantId Local assistant ID
     * @param string $title Optional title for the thread
     * @param string $description Optional description
     * @param array $metadata Optional metadata
     * @return Thread
     **/
    public function createThread(int $assistantId, string $title = '', string $description = '', array $metadata = []): Thread
    {
        $assistant = Assistant::findOrFail($assistantId);

        $response = $this->api->createThread();

        $thread = new Thread();
        $thread->thread_id = $response['id'];
        $thread->assistant_id = $assistant->id;
        $thread->title = $title;
        $thread->description = $description;
        $thread->metadata = $metadata;
        $thread->is_active = true;
        $thread->save();

        return $thread;
    }

    /**
     * Get threads for an assistant
     *
     * @param int $assistantId
     * @param bool $onlyActive Whether to retrieve only active threads
     * @return Collection
     */
    public function getThreads(int $assistantId, bool $onlyActive = true): Collection
    {
        $query = Thread::where('assistant_id', $assistantId);

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Get a single thread
     *
     * @param int $threadId
     * @return Thread
     */
    public function getThread(int $id): Thread
    {
        return Thread::findOrFail($id);
    }

    /**
     * Add a message to a thread and get the assistant's response
     *
     * @param int $threadId Local thread ID
     * @param string $content Message content
     * @param array $parameters Additional parameters
     * @return array Response with messages
     * @throws Exception
     */
    public function sendMessage(int $threadId, string $content, array $parameters = []): array
    {
        $thread = Thread::with('assistant')->findOrFail($threadId);
        $assistant = $thread->assistant;

        // Add message to thread via API
        $userMessage = $this->api->addMessage($thread->thread_id, $content);

        $userMsgRecord = new Message();
        $userMsgRecord->message_id = $userMessage['id'];
        $userMsgRecord->thread_id = $thread->id;
        $userMsgRecord->role = 'user';
        $userMsgRecord->content = $content;
        $userMsgRecord->save();

        // Get assistant's response
        $response = $this->api->getResponse($thread->thread_id, $assistant->assistant_id, $parameters);

        // Add some debugging to understand the response structure
        \Log::info('Assistant API response:', ['response' => $response]);

        // Check response structure - different ways to handle depending on the actual structure
        if (isset($response['messages']) && isset($response['messages']['data']) && !empty($response['messages']['data'])) {
            $latestMessage = $response['messages']['data'][0];

            // Only process if it's the assistant's message (not our user message)
            if ($latestMessage['role'] === 'assistant') {
                $messageContent = '';

                // Again, we need to check the actual structure of the content
                if (isset($latestMessage['content'][0]['text']['value'])) {
                    $messageContent = $latestMessage['content'][0]['text']['value'];
                } elseif (isset($latestMessage['content']) && is_string($latestMessage['content'])) {
                    // Fallback in case content is directly a string
                    $messageContent = $latestMessage['content'];
                }

                if (empty($messageContent)) {
                    \Log::warning('Empty message content from assistant:', ['latestMessage' => $latestMessage]);
                    throw new Exception('Received empty response from assistant');
                }

                $assistantMsgRecord = new Message();
                $assistantMsgRecord->message_id = $latestMessage['id'];
                $assistantMsgRecord->thread_id = $thread->id;
                $assistantMsgRecord->role = 'assistant';
                $assistantMsgRecord->content = $messageContent;
                $assistantMsgRecord->save();

                return [
                    'success' => true,
                    'message' => $messageContent,
                    'message_id' => $assistantMsgRecord->id,
                    'log_id' => $response['logId'] ?? null
                ];
            }
        } elseif (isset($response['message'])) {
            // Alternative structure - the message might be directly in the response
            return [
                'success' => true,
                'message' => $response['message'],
                'message_id' => $userMsgRecord->id, // We may not have the ID in this case
                'log_id' => $response['logId'] ?? null
            ];
        }

        \Log::error('Failed to parse assistant response:', ['response' => $response]);
        throw new Exception('Failed to get assistant response');
    }

    /**
     * Get messages from a thread
     *
     * @param int $threadId Local thread ID
     * @param int $limit Maximum number of messages to retrieve
     * @param string $order Sort order (asc/desc)
     * @return \October\Rain\Database\Collection
     */
    public function getMessages(int $threadId, int $limit = 50, string $order = 'desc')
    {
        return Message::where('thread_id', $threadId)
            ->orderBy('created_at', $order)
            ->limit($limit)
            ->get();
    }

    /**
     * Quick conversation
     * @param int $assistantId Local assistant ID
     * @param string $prompt User prompt
     * @param int|null $threadId Optional local thread ID for continued conversation
     * @param array $parameters Additional parameters
     * @return array Response with message content and thread info
     * @throws Exception
     */
    public function conversation(int $assistantId, string $prompt, ?int $threadId = null, array $parameters = []): array
    {
        $assistant = Assistant::findOrFail($assistantId);

        // Get or create thread
        if ($threadId) {
            $thread = Thread::findOrFail($threadId);

            // Ensure thread
            if ($thread->assistant_id != $assistantId) {
                throw new Exception('Thread does not belong to this assistant');
            }
        } else {
            $thread = $this->createThread($assistantId);
        }

        $response = $this->sendMessage($thread->id, $prompt, $parameters);

        return [
            'message' => $response['message'],
            'thread_id' => $thread->id,
            'log_id' => $response['log_id'] ?? null
        ];
    }

    /**
     * Delete an assistant
     *
     * @param string $assistantId Assistant ID
     * @return bool Success status
     */
    public function deleteAssistant(string $assistantId):bool
    {
        $this->api->deleteAssistant($assistantId);

        return true;
    }

    /**
     * Import assistants from OpenAI API
     *
     * @return int Number of imported assistants
     */
    public function importAssistants(): int
    {
        $response = $this->api->listAssistants();

        if (!isset($response['data']) || empty(response['data'])) {
            return 0;
        }

        $importCount = 0;

        foreach ($response['data'] as $assistantData) {
            $existingAssistant = Assistant::where('assistant_id', $assistantData['id'])->first();

            if (!$existingAssistant) {
                $assistant = new Assistant();
                $assistant->name = $assistantData['name'] ?? 'Unnamed Assistant';
                $assistant->assistant_id = $assistantData['id'];
                $assistant->description = '';
                $assistant->instructions = $assistantData['instructions'] ?? '';
                $assistant->model = $assistantData['model'] ?? '';
                $assistant->tools = $assistantData['tools'] ?? [];
                $assistant->is_active = true;
                $assistant->save();

                $importCount++;
            }
        }

        return $importCount;
    }

    /**
     * Get assistant response after adding a message
     *
     * @param string $threadId The OpenAI thread ID
     * @param string $assistantId The OpenAI assistant ID
     * @param array $parameters Additional parameters
     * @return array Response data
     */
    public function getAssistantResponse(string $threadId, string $assistantId, array $parameters = []): array
    {
        // Get an instance of the OpenAIAssistant class
        $assistant = new OpenAIAssistant();

        // Get the response
        return $assistant->getResponse($threadId, $assistantId, $parameters);
    }

    /**
     * Get function handler for an assistant
     *
     * @param Assistant $assistant The assistant model
     * @return object|null Function handler instance
     */
    public function getFunctionHandler(Assistant $assistant)
    {
        $handlerId = $assistant->handler_id ?: 'default';
        $handlers = $this->getRegisteredHandlers();

        if (isset($handlers[$handlerId])) {
            return new $handlers[$handlerId]();
        }

        return null;
    }

    /**
     * Get all registered function handlers from plugins
     *
     * @return array Handler class mapping
     */
    protected function getRegisteredHandlers()
    {
        $handlers = [];

        // Get handlers from all plugins
        $plugins = \System\Classes\PluginManager::instance()->getPlugins();
        foreach ($plugins as $plugin) {
            if (method_exists($plugin, 'registerAssistantFunctionHandlers')) {
                $handlers = array_merge($handlers, $plugin->registerAssistantFunctionHandlers());
            }
        }

        return $handlers;
    }
}

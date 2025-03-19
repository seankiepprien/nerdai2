<?php namespace Nerd\Nerdai\Components;

use Cms\Classes\ComponentBase;
use Nerd\Nerdai\Classes\Services\AssistantService;
use Nerd\Nerdai\Models\Assistant;
use Nerd\Nerdai\Models\Thread;;
use Nerd\Nerdai\Models\Message;
use Nerd\NerdAI\Models\NerdAiSettings as Settings;
use Log;
use Auth;
use Flash;
use Session;
use Validator;
use Input;
use Pusher\Pusher;
use Exception;

/**
 * AssistantChat Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AssistantChat extends ComponentBase
{
    protected $assistantService;
    protected $pusher;

    public function componentDetails()
    {
        return [
            'name' => 'Assistant Chat Component',
            'description' => 'A real-time chat component for interacting with OpenAI Assistant.'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [
            'assistantId' => [
                'title' => 'Assistant',
                'description' => 'Select which assistant to use for this chat',
                'type' => 'dropdown',
                'default' => '',
                'required' => true
            ],
            'maxMessages' => [
                'title' => 'Max Messages',
                'decsription' => 'Maximum number of messages to load initially',
                'type' => 'string',
                'default' => '50',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Please enter a number.'
            ],
            'threadPersistKey' => [
                'title' => 'Thread Persist Key',
                'description' => 'The key used to persist the thread ID in the session. (leave empty to start new thread each visit)',
                'type' => 'string',
                'default' => 'nerdai_assistant_thread'
            ],
            'channelPrefix' => [
                'title' => 'Pusher Channel Prefix',
                'description' => 'The prefix to use for the Pusher channels. (leave empty to use the default)',
                'type' => 'string',
                'default' => 'assistant-chat'
            ],
            'userAvatar' => [
                'title' => 'User Avatar',
                'description' => 'The URL to use for the user avatar.',
                'type' => 'string',
                'default' => 'https://cdn-icons-png.flaticon.com/512/149/149072.png'
            ],
            'assistantAvatar' => [
                'title' => 'Assistant Avatar',
                'description' => 'The URL to use for the assistant avatar.',
                'type' => 'string',
                'default' => 'https://cdn-icons-png.flaticon.com/512/149/149071.png'
            ]
        ];
    }

    public function getAssistantIdOptions()
    {
        return Assistant::where('is_active', true)
            ->lists('name', 'id');
    }

    public function onRun()
    {
        $this->setupPusher();
        $this->addCss(url('/plugins/nerd/nerdai/assets/css/assistant.css'));
        $this->addJs(url('/plugins/nerd/nerdai/assets/js/assistant.js'));

        $this->page['chatId'] = uniqid('chat_');

        $assistantId = $this->property('assistantId');
        $this->page['assistant'] = Assistant::findOrFail($assistantId);

        $this->page['userAvatar'] = $this->property('userAvatar');
        $this->page['assistantAvatar'] = $this->property('assistantAvatar');

        $this->assistantService = new AssistantService();

        $this->initializeThread();


        $this->loadMessages();

        // Pass Pusher channel info to the page
        // Pass Pusher channel info to the page
        $channelName = $this->getChannelName();
        \Log::info('Setting channel name: ' . $channelName);
        $this->page['pusherChannel'] = $channelName;
        $this->page['pusherChannel'] = $channelName;
        $this->page['pusherKey'] = config('nerd.pusher.key');
        $this->page['pusherCluster'] = config('nerd.pusher.options.cluster');
    }

    public function setupPusher()
    {
        $key = config('nerd.pusher.key');
        $secret = config('nerd.pusher.secret');
        $appId = config('nerd.pusher.app_id');
        $cluster = config('nerd.pusher.options.cluster');

        if (empty($key) || empty($secret) || empty($appId) || empty($cluster)) {
            throw new Exception('Pusher credentials are not set.');
        }

        $options = [
            'cluster' => $cluster,
            'useTLS' => true
        ];

        $this->pusher = new Pusher($key, $secret, $appId, $options);
    }

    protected function getChannelName()
    {
        $prefix = $this->property('channelPrefix');

        // First, try to get the thread from the current request
        $threadId = post('thread_id');

        // If not in post data, try to get from page variable
        if (!$threadId && isset($this->page['thread']) && $this->page['thread']) {
            $threadId = $this->page['thread']->id;
        }

        $channelName = "{$prefix}-{$threadId}";
        \Log::info('Generated channel name: ' . $channelName, [
            'prefix' => $prefix,
            'thread_id' => $threadId,
            'post_thread_id' => post('thread_id'),
            'page_thread_id' => isset($this->page['thread']) ? $this->page['thread']->id : null
        ]);

        return $channelName;
    }

    protected function initializeThread()
    {
        $assistantId = $this->property('assistantId');
        $persistKey = $this->property('threadPersistKey');

        // Check if we have a stored thread ID
        $threadId = null;
        if ($persistKey) {
            $threadId = Session::get($persistKey);

            if ($threadId) {
                try {
                    // Try to get the thread and verify it belongs to this assistant
                    $thread = Thread::findOrFail($threadId);
                    if ($thread->assistant_id != $assistantId) {
                        $threadId = null;
                    } else {
                        $this->page['thread'] = $thread;
                        return;
                    }
                } catch (Exception $e) {
                    // Thread not found, will create a new one
                    $threadId = null;
                }
            }
        }

        // Create a new thread if needed
        if (!$threadId) {
            $thread = $this->assistantService->createThread(
                $assistantId,
                'Test Chat',
                'Thread created from test chat component'
            );

            if ($persistKey) {
                Session::put($persistKey, $thread->id);
            }

            $this->page['thread'] = $thread;
        }
    }

    protected function loadMessages()
    {
        $threadId = $this->page['thread']->id;
        $maxMessages = (int)$this->property('maxMessages', 50);

        $messages = $this->assistantService->getMessages($threadId, $maxMessages, 'desc');

        // Reverse the messages to show oldest first
        $this->page['messages'] = $messages->reverse();
    }

    /**
     * Send a message in a thread
     */
    public function onSendMessage()
    {
        try {
            // Validate input
            $data = post();
            $rules = [
                'message' => 'required',
                'thread_id' => 'required'
            ];

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                throw new Exception('Please enter a message');
            }

            // Get thread ID from post data
            $threadId = post('thread_id');

            // Get the thread from the database
            $thread = Thread::where('thread_id', $threadId)->first();
            if (!$thread) {
                throw new Exception('Thread not found');
            }

            // Get the assistant
            $assistant = Assistant::find($thread->assistant_id);
            if (!$assistant) {
                throw new Exception('Assistant not found');
            }

            $message = $data['message'];

            // Add user message to thread
            $this->assistantService->addMessage($threadId, $message);

            // Broadcast user message to Pusher
            $this->broadcastMessage([
                'role' => 'user',
                'content' => $message,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Get assistant response
            $this->broadcastMessage([
                'type' => 'status',
                'status' => 'typing',
                'timestamp' => now()->toDateTimeString()
            ]);

            // Run assistant with function handling
            $result = $this->assistantService->runAssistant(
                $thread->thread_id,
                $assistant->assistant_id,
                [
                    // Enable function calling if the assistant has tools
                    'tools' => $assistant->tools ?? []
                ]
            );

            // Extract message content
            $messageContent = $this->extractMessageContent($result['messages']);

            // Broadcast assistant response
            $this->broadcastMessage([
                'role' => 'assistant',
                'content' => $messageContent,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Broadcast completion status
            $this->broadcastMessage([
                'type' => 'status',
                'status' => 'complete',
                'timestamp' => now()->toDateTimeString()
            ]);

            return [
                'success' => true
            ];
        } catch (Exception $e) {
            // Handle error
            $this->broadcastMessage([
                'type' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function startAssistantResponse($threadId, $responseData)
    {
        try {
            // Broadcast "typing" indicator
            $this->broadcastMessage([
                'type' => 'status',
                'status'=> 'typing',
                'timestamp' => now()->toDateTimeString()
            ]);

            // Short delay to simulate processing and show typing indicator
            // In a production environment, this would be handled by a queue job
            sleep(1);

            // Check if we received a valid response
            if (isset($responseData['message']) && !empty($responseData['message'])) {
                // Broadcast the assistant response
                $this->broadcastMessage([
                    'role' => 'assistant',
                    'content' => $responseData['message'],
                    'timestamp' => now()->toDateTimeString()
                ]);

                \Log::info('Assistant response sent successfully', [
                    'thread_id' => $threadId,
                    'message_length' => strlen($responseData['message'])
                ]);
            } else {
                // Handle empty response
                \Log::warning('Empty assistant response received', [
                    'thread_id' => $threadId,
                    'response_data' => $responseData
                ]);

                // Broadcast a fallback message
                $this->broadcastMessage([
                    'role' => 'assistant',
                    'content' => "I'm sorry, I wasn't able to generate a response. Please try asking in a different way.",
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Broadcast "done" status
            $this->broadcastMessage([
                'type' => 'status',
                'status' => 'complete',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (Exception $e) {
            \Log::error("Error sending assistant response: " . $e->getMessage(), [
                'thread_id' => $threadId,
                'exception' => $e
            ]);

            // Broadcast error message
            $this->broadcastMessage([
                'type' => 'error',
                'message' => 'Sorry, there was an error processing your request. Please try again.',
                'timestamp' => now()->toDateTimeString()
            ]);
        }
    }

    public function onClearChat()
    {
        $assistantId = $this->property('assistantId');
        $persistKey = $this->property('threadPersistKey');

        // Create a new thread
        $thread = $this->assistantService->createThread(
            $assistantId,
            'Test Chat',
            'Thread created from test chat component'
        );

        if ($persistKey) {
            Session::put($persistKey, $thread->id);
        }

        $this->page['thread'] = $thread;
        $this->page['messages'] = collect([]);

        // Update the Pusher channel name
        $channelName = $this->getChannelName();
        $this->page['pusherChannel'] = $channelName;

        return [
            'success' => true,
            '#chat-messages-container' => $this->renderPartial('@messages'),
            'pusherChannel' => $channelName
        ];
    }

    /**
     * Broadcast a message via Pusher
     *
     * @param array $data Message data to broadcast
     * @return void
     */
    protected function broadcastMessage(array $data)
    {
        try {
            // Check if Pusher is configured
            if (!$this->pusher) {
                $this->setupPusher();
            }

            // Get the channel name
            $channelName = $this->getChannelName();
            \Log::info('Broadcasting message to channel: ' . $channelName, ['data' => $data]);

            // Trigger the event on the channel
            $this->pusher->trigger($channelName, 'chat-message', $data);

        } catch (Exception $e) {
            \Log::error('Error broadcasting message: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e
            ]);
        }
    }

    /**
     * Extract message content from OpenAI messages response
     *
     * @param array $messages Messages response
     * @return string Message content
     */
    protected function extractMessageContent(array $messages): string
    {
        if (!isset($messages['data']) || empty($messages['data'])) {
            return 'No response received';
        }

        $message = $messages['data'][0];

        // Check if it's an assistant message
        if ($message['role'] !== 'assistant') {
            return 'Unexpected message role';
        }

        // Extract the content
        if (isset($message['content']) && is_array($message['content'])) {
            // New format with content parts
            $textParts = array_filter($message['content'], function($part) {
                return $part['type'] === 'text';
            });

            if (!empty($textParts)) {
                $firstTextPart = reset($textParts);
                return $firstTextPart['text']['value'] ?? '';
            }
        } elseif (isset($message['content']) && is_string($message['content'])) {
            // Old format with simple string content
            return $message['content'];
        }

        return 'Unable to extract message content';
    }
}

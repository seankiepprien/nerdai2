<?php namespace Nerd\Nerdai\FormWidgets;

use Backend\Classes\FormWidgetBase;
use OpenAI;
use Pusher\Pusher;
use Nerd\Nerdai\Models\NerdAiSettings as Settings;

/**
 * ChatWidget Form Widget
 *
 * @link https://docs.octobercms.com/3.x/extend/forms/form-widgets.html
 */
class ChatWidget extends FormWidgetBase
{
    protected $defaultAlias = 'nerdai_chat_widget';

    protected $client;
    public function init()
    {
        $apikey = Settings::get('openai_api_key');
        $this->client = OpenAI::factory()->withApiKey($apikey)->make();
    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('chatwidget');
    }

    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
    }

    public function loadAssets()
    {
        $this->addCss('css/chatwidget.css');
        $this->addJs('js/chatwidget.js');
    }

    public function getSaveValue($value)
    {
        return $value;
    }

    public function onSendMessage()
    {
        try {
            $message = post('message');

            // Broadcast user message
            $this->broadcastMessage('user', $message);

            // Process assistant response
            $response = $this->queryAssistant($message);

            // Broadcast assistant response
            $this->broadcastMessage('assistant', $response);

            // Return JSON response (important to avoid page refresh)
            return ['status' => 'success', 'message' => $response];

        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function queryAsssitant($message)
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => ['role' => 'user', 'content' => $message],
            'max_tokens' => 500
        ]);

        return $response->toArray()['choices'][0]['message']['content'] ?? 'No response';
    }

    protected function broadcastMessage($role, $message)
    {
        $pusher = new Pusher(
            config('services.pusher.key'),
            config('services.pusher.secret'),
            config('services.pusher.app_id'),
            ['cluster' => config('services.pusher.cluster'), 'useTLS' => true]
        );

        $pusher->trigger('chat-channel', 'new-message', ['role' => $role, 'message' => $message]);
    }
}

<?php

namespace Nerd\Nerdai\Classes\tasks;

use Nerd\Nerdai\Classes\TaskInterface;

class AssistantTask implements TaskInterface
{
    public $mode;
    public $assistantId;
    public $threadId;

    public function __construct(string $assistantId = null, string $threadId = null) {
        $this->mode = 'assistant-api';
        $this->assistantId = $assistantId;
        $this->threadId = $threadId;
    }
    public function makePrompt(array|string $input, array $options): string
    {
        // For assistant API, we don't need complex prompt engineering as that's handled by the assistant instructions
        if (is_array($input) && isset($input['text'])) {
            return $input['text'];
        }

        return $input;
    }

    public function getResponse(array|string $input, array $options = []): array
    {
        $prompt = $this->makePrompt($input, $options);

        $assistantId = $options['assistant_id'] ?? $this->assistantId;
        $threadId = $options['thread_id'] ?? $this->threadId;

        if (!$assistantId) {
            throw new \Exception('Assistant ID is required for AssistantTask');
        }

        return [
            'prompt' => $prompt,
            'assistant_id' => $assistantId,
            'thread_id' => $threadId,
            'options' => $options
        ];
    }
}

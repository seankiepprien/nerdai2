<?php

namespace Nerd\Nerdai\Classes\tasks;

use Nerd\Nerdai\Classes\tasks\BuildTask;

class VisionTask extends BuildTask
{
    public function __construct()
    {
        $this->mode = 'vision-analysis';
    }

    public function makePrompt(array|string $input, array $options): string
    {
        // Override parent method to handle image data differently
        if (is_array($input) && isset($input['image']) && isset($input['prompt'])) {
            $promptBuilder = new \Nerd\Nerdai\Classes\Prompts\VisionPromptBuilder();
            $this->prompt = $promptBuilder->toPrompt($input, $this->mode);
            return $this->prompt;
        }

        throw new \Exception('Vision task requires both image and a prompt in input array');
    }

    public function getResponse(string|array $input, array $options = []): array
    {
        $this->makePrompt($input);

        return [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => $this->prompt
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $input['image'],
                            'detail' => $options['detail'] ?? 'auto'
                        ]
                    ]
                ]
            ]
        ];
    }
}

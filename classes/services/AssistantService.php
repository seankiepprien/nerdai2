<?php

namespace Nerd\Nerdai\Classes\services;

use Exception;
use Nerd\Nerdai\Classes\Models\OpenAI\OpenAIAssistant;
use Nerd\Nerdai\Models\Assistant;
use Nerd\Nerdai\Models\Thread;
use Nerd\Nerdai\Models\Message;
use Nerd\Nerdai\Models\NerdAiSettings as Settings;

class AssistantService
{
    protected $api;

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
}

<?php namespace Nerd\Nerdai\Classes;

use Nerd\Nerdai\Classes\Clients\OpenAIClient;
use Nerd\Nerdai\Classes\Models\OpenAI\OpenAIAssistant;
class ClientFactory
{
    public static function createOpenAIClient(string $api_key, string $organization, array $parameters = []): ClientInterface
    {
        return new OpenAIClient($api_key, $organization, $parameters);
    }

    public static function createOpenAIAssistant(): AssistantInterface
    {
        return new OpenAIAssistant();
    }
}

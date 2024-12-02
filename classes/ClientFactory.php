<?php namespace Nerd\Nerdai\Classes;

use Nerd\Nerdai\Classes\Clients\OpenAIClient;

class ClientFactory
{
    public static function createOpenAIClient(string $api_key, string $organization, array $parameters = []): ClientInterface
    {
        return new OpenAIClient($api_key, $organization, $parameters);
    }
}

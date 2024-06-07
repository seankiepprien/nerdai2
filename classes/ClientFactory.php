<?php namespace Nerd\Nerdai\Classes;

use Nerd\Nerdai\Classes\ClientInterface;
use Nerd\Nerdai\Classes\Clients\OpenAICompletionClient;
use Nerd\Nerdai\Classes\Clients\HuggingfaceClient;


class ClientFactory
{
    public static function createOpenAIClient(string $api_key, string $organization, array $parameters): ClientInterface
    {
        return new OpenAICompletionClient($api_key, $organization, $parameters);
    }

    public static function createOpenAIChatlient(string $api_key, string $organization, array $parameters): ClientInterface{
        return new OpenAIChatClient($api_key, $organization, $parameters);
    }

    public static function createHuggingfaceClient(string $api_key, string $organization, array $parameters): ClientInterface
    {
        return new HuggingfaceClient($api_key, $organization, $parameters);
    }
}

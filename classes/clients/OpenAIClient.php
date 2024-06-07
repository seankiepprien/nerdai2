<?php namespace Nerd\Nerdai\Classes\Clients;

use Nerd\Nerdai\Classes\ClientInterface;

use Nerd\Nerdai\Classes\Prompts\PromptBuilder;
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
    protected string $organization = null;
    protected array $headers = ['OpenAI-Beta', 'assistants=v1'];
    protected array $parameters = [];
    protected string $model = 'gpt-3.5-turbo-instruct';
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

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function makeClient(): void
    {
        $clientFactory = OpenAI::factory();
        $clientFactory->withApiKey($this->apiKey);
        $clientFactory->withOrganization($this->organization);
        $clientFactory->withHeaders(...$this->headers);
    }
}

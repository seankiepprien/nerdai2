<?php namespace Nerd\Nerdai\Classes;

interface ClientInterface
{
    public function __construct(string $apiKey, string $organization = null, array $parameters);
    public function setApiKey(string $key): void;
    public function setOrganization(string $organization): void;
    public function setHttpHeaders(array $headers): void;
    public function setModel(string $model): void;
    public function setParameters(array $parameters): void;
    public function getModel(): string;
    public function getParameters(): array;
    public function makeClient(): object;

    /**
     * Create a new assistant
     *
     * @param string $name Assistant name
     * @param string $instructions Instructions for the assistant
     * @param string $model Model to use
     * @param array $tools Optional tools
     * @return array Assistant data
     */
    public function createAssistant(string $name, string $instructions, string $model, array $tools = []): array;

    /**
     * Retrieve an assistant
     *
     * @param string $assistantId Assistant ID
     * @return array Assistant data
     */
    public function getAssistant(string $assistantId): array;

    /**
     * Create a new thread
     *
     * @param array $messages Initial messages
     * @return array Thread data
     */
    public function createThread(array $messages = []): array;

    /**
     * Add a message to a thread
     *
     * @param string $threadId Thread ID
     * @param string $role Message role
     * @param string $content Message content
     * @param array $attachments Optional attachments
     * @return array Message data
     */
    public function addMessage(string $threadId, string $role, string $content, array $attachments = []): array;

    /**
     * Create a run (execute assistant on thread)
     *
     * @param string $threadId Thread ID
     * @param string $assistantId Assistant ID
     * @param array $params Additional parameters
     * @return array Run data
     */
    public function createRun(string $threadId, string $assistantId, array $params = []): array;

    /**
     * Wait for a run to complete
     *
     * @param string $threadId Thread ID
     * @param string $runId Run ID
     * @param int $timeout Maximum wait time
     * @param int $pollingInterval Time between checks
     * @return array Completed run data
     */
    public function waitForRun(string $threadId, string $runId, int $timeout = 300, int $pollingInterval = 2): array;
}

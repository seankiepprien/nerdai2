<?php

namespace Nerd\Nerdai\Classes;

interface AssistantInterface
{
    /**
     * Create a new assistant with specified parameters
     *
     * @param string $name Name of the assistant
     * @param string $instructions Instructions for the assistant
     * @param array $tools Tools to enable for the assistant
     * @param array $parameters Additional parameters for the assistant
     * @return array Assistant data
     */
    public function createAssistant(string $name, string $instructions, array $tools = [], array $parameters = []): array;

    /**
     * Create a new thread for conversation
     *
     * @param array $initialMessages Optional initial messages for the thread
     * @return array Thread data
     */
    public function createThread(array $initialMessages = []): array;

    /**
     * Add a user message to a thread
     *
     * @param string $threadId The ID of the thread
     * @param string $content Message content
     * @param array $attachments Optional file attachments
     * @return array Message data
     */
    public function addMessage(string $threadId, string $content, array $attachments = []): array;

    /**
     * Run an assistant on a thread
     *
     * @param string $threadId Thread ID
     * @param string $assistantId Assistant ID
     * @param array $parameters Additional parameters
     * @return array Response data
     */
    public function runAssistant(string $threadId, string $assistantId, array $parameters = []): array;

    /**
     * Get the messages from a thread
     *
     * @param string $threadId Thread ID
     * @param array $parameters Optional filtering parameters
     * @return array Messages data
     */
    public function getThreadMessages(string $threadId, array $parameters = []): array;
}

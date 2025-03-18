<?php

namespace Nerd\Nerdai\Classes;

use Exception;
class TaskFactory
{
    /**
     * Dynamically resolve a TaskInterface implementation based on the given mode.
     *
     * @param string $mode The mode to resolve the task for.
     * @return TaskInterface
     * @throws Exception If no task is found for the given mode.
     */
    public static function resolve(string $mode): TaskInterface
    {
        switch ($mode) {
            case 'complete':
                return new \Nerd\Nerdai\Classes\tasks\CompleteTask();
            case 'elaborate':
                return new \Nerd\Nerdai\Classes\tasks\ExpandTask();
            case 'prompt':
                return new \Nerd\Nerdai\Classes\tasks\PromptTask();
            case 'rewrite':
                return new \Nerd\Nerdai\Classes\tasks\RewriteTask();
            case 'summarize':
                return new \Nerd\Nerdai\Classes\tasks\SummarizeTask();
            case 'html-code':
                return new \Nerd\Nerdai\Classes\tasks\HtmlCodeTask();
            case 'vehicle-description':
                return new \Nerd\Nerdai\Classes\tasks\VehicleDescriptionTask();
            case 'vision':
                return new \Nerd\Nerdai\Classes\tasks\VisionTask();
            case 'assistant':
                $assistantid = $options ['assistant_id'] ?? null;
                $threadId = $options['thread_id'] ?? null;
                return new \Nerd\Nerdai\Classes\tasks\AssistantTask($assistantid, $threadId);
            default:
                throw new Exception('No task found for mode: ' . $mode);
        }
    }
}

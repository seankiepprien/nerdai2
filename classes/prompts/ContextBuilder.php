<?php

namespace Nerd\Nerdai\Classes\prompts;

class ContextBuilder
{
    /**
     * Build context-related prompt.
     *
     * @param string $context The context to include in the prompt.
     * @return string The built context prompt.
     */
    public function buildContextPrompt($context): string
    {
        return $context ? "### CONTEXT: ### \n This is the context you should be aware of but you are not required to repeat it in your response: " . $context . ". " : "";
    }
}

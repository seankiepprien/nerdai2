<?php

namespace Nerd\Nerdai\Classes\prompts;

class LanguageBuilder
{
    /**
     * Build language-related prompt.
     *
     * @param string $language The language to include in the prompt.
     * @return string The built language prompt.
     */
    public function buildLanguagePrompt($language): string
    {
        return $language ? sprintf("### LANGUAGE: ### \n When responding, your reply should be in '%s' language. ", $language . "\n") : "";
    }
}

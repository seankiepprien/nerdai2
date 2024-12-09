<?php

namespace Nerd\Nerdai\Classes\prompts;

class PersonaBuilder
{
    /**
     * Build persona-related prompt.
     *
     * @param string $persona The persona to include in the prompt.
     * @return string The built persona prompt.
     */
    public function buildPersonaPrompt($persona): string
    {
        return $persona ? "This is your persona and it defines your characteristic: " . $persona . ". " : "";
    }
}

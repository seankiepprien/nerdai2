<?php

namespace Nerd\Nerdai\Classes\prompts;

class IntonationBuilder
{
    /**
     * Build intonation-related prompt.
     *
     * @param string $intonation The desired intonation for the prompt.
     * @return string The built intonation prompt.
     */
    public function buildIntonationPrompt($intonation): string
    {
        return $intonation ? sprintf("### INTONATION: ### \nYour prompt should be in '%s' intonation. ", $intonation) : "";
    }
}

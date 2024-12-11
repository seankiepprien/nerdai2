<?php

namespace Nerd\Nerdai\Classes\prompts;

class DealerContextBuilder
{
    /**
     * Build the dealer-side context side of the prompt.
     *
     * @param string $dealerContext The context to include in the prompt.
     * @return string The built context prompt.
     */
    public function buildDealerContextPrompt($dealerContext): string
    {
        return $dealerContext ? "### DEALER ADDITIONAL CONTEXT: ### \n This is additional context provided by the dealership manager, please consider this information with the utmost priority: " . $dealerContext . ". \n" : "";
    }
}

<?php

namespace Nerd\Nerdai\Classes\prompts;

class DealerInformationBuilder
{
    /**
     * Build the Dealer's informations part in the prompt.
     *
     * @params array $dealerInfo
     * @return string The built DealerInfo prompt
     */
    public function buildDealerInformationPrompt($dealerInfo)
    {
        return $dealerInfo ? "### DEALER INFORMATION: ### \n This is all the information about the car dealership you will impersonate: " . $dealerInfo  . ". \n" : "";
    }
}

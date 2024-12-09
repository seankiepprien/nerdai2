<?php

namespace Nerd\Nerdai\Classes\prompts;

use Nerd\Nerdai\Models\NerdAiSettings as Settings;

class PromptBuilder
{
    protected $prompt;
    protected $includePersona = true;
    protected $includeContext = true;
    protected $includeIntonation = true;
    protected $includeLanguage = true;

    public function addPrompt($text): self
    {
        $this->prompt .= $text . ' ';
        return $this;
    }

    public function withoutPersona(): self
    {
        $this->includePersona = false;
        return $this;
    }

    public function withoutContext(): self
    {
        $this->includeContext = false;
        return $this;
    }

    public function withoutIntonation(): self
    {
        $this->includeIntonation = false;
        return $this;
    }

    public function withoutLanguage(): self
    {
        $this->includeLanguage = false;
        return $this;
    }

    public function toPrompt($value, $mode): string
    {
        $persona = Settings::get('persona');
        $context = Settings::get('context');
        $intonation = Settings::get('intonation');
        $language = Settings::get('language');

        $personaBuilder = new PersonaBuilder();
        $contextBuilder = new ContextBuilder();
        $intonationBuilder = new IntonationBuilder();
        $languageBuilder = new LanguageBuilder();

        if ($this->includePersona) {
            $this->prompt .= $personaBuilder->buildPersonaPrompt($persona);
        }

        if ($this->includeContext) {
            $this->prompt .= $contextBuilder->buildContextPrompt($context);
        }

        if ($this->includeIntonation) {
            $this->prompt .= $intonationBuilder->buildIntonationPrompt($intonation);
        }

        if ($this->includeLanguage) {
            $this->prompt .= $languageBuilder->buildLanguagePrompt($language);
        }

        switch ($mode) {
            case 'text-rewrite':
                return $this->prompt . 'Without repeating or explaining your persona, please rephrase the following sentence while keeping the word count approximately the same: ' . $value;
            case 'text-completion':
                return $this->prompt . 'Without repeating or explaining your persona, complete the sentence: ' . $value;
            case 'text-summarization':
                return $this->prompt . 'Without repeating or explaining your persona, please summarize the following sentences into a much shorter result: ' . $value;
            case 'text-expansion':
                return $this->prompt . 'Without repeating or explaining your persona, please further elaborate on these sentences: ' . $value;
            case 'text-prompt':
                return $this->prompt . 'Without repeating or explaining your persona, please respond to the following: ' . $value;
            case 'html-code':
                return $this->prompt . 'Without repeating or explaining your persona, without the <html>, <head>, <body> sections or any unnecessary tags, please generate SEO and accessibility friendly HTML code for the following: ' . $value;
            case 'vehicle-description':
                return $this->prompt . "Without repeating or explaining your persona, without the <html>, <head>, <body> sections or any unnecessary tags, please generate SEO and accessibility friendly HTML code Produce a professional and engaging description for a vehicle based on the dataset below. The description must be optimized for the web and contain every specification about the vehicle:
            - A captivating introduction presenting the vehicle and its unique qualities. A clear indication of the vehicle's status.
            - Be careful, the price is formatted without the comma before the last two zeros.
            - Specific user benefits, including performance, technological features and comfort.
            - A mention of price and promotions, followed by a call to action to visit the dealership.
            - A mention of the rigorous inspection carried out on each vehicle at the dealership.

            Focus on the following strengths: powertrain, interior comfort, safety features and technology. Be sure to include keywords like “Acura MDX 2020” to optimize content for search engines, and finish the description with a short but engaging description of the dealership in context.

            using these specifications: " . $value;
            default:
                return $this->prompt . $value;
        }
    }
}

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
    protected $includeDealerInfo = true;
    protected $includeDealerAdditionalContext = true;
    protected $includeAdditionalInstructions = true;
    protected $includeVehicleInfo = true;

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

    public function withoutDealerInfo(): self
    {
        $this->includeDealerInfo = false;
        return $this;
    }

    public function withoutDealerAdditionalContext(): self
    {
        $this->includeDealerAdditionalContext = false;
        return $this;
    }

    public function withoutAdditionalInstructions(): self
    {
        $this->includeAdditionalInstructions = false;
        return $this;
    }

    public function withoutVehicleInfo(): self
    {
        $this->includeVehicleInfo = false;
        return $this;
    }

    public function toPrompt($value, $mode, $dealerInfo, $vehicleInfo): string
    {
        $persona = Settings::get('persona');
        $context = Settings::get('context');
        $intonation = Settings::get('intonation');
        $language = Settings::get('language');
        $dealerContext = $dealerInfo?->ai_additional_context ?? null;

        $personaBuilder = new PersonaBuilder();
        $contextBuilder = new ContextBuilder();
        $intonationBuilder = new IntonationBuilder();
        $languageBuilder = new LanguageBuilder();
        $dealerInfoBuilder = new DealerInformationBuilder();
        $dealerContextBuilder = new DealerContextBuilder();
        $vehicleInfoBuilder = new VehicleInfoBuilder();

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

        if ($this->includeDealerInfo) {
            $this->prompt .= $dealerInfoBuilder->buildDealerInformationPrompt($dealerInfo);
        }

        if ($this->includeDealerAdditionalContext && $dealerContext !== null) {
            $this->prompt .= $dealerContextBuilder->buildDealerContextPrompt($dealerContext);
        }

        if ($this->includeVehicleInfo && $vehicleInfo !== null) {
            $this->prompt .= $vehicleInfoBuilder->buildVehicleInfoPrompt($vehicleInfo);
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
                return $this->prompt . $value;
            default:
                return $this->prompt . $value;
        }
    }
}

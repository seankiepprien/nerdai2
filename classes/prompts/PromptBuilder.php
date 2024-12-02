<?php

namespace Nerd\Nerdai\Classes\Prompts;

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
            default:
                return $this->prompt . $value;
        }
    }
}

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
}

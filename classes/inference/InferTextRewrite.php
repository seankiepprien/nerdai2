<?php

namespace Nerd\Nerdai\Classes\inference;

use Nerd\NerdAI\Classes\Prompts\PromptBuilder;

class InferTextRewrite extends InferText
{
    public $mode;
    public function __construct(string $mode = null)
    {
        $this->mode = $mode ?: 'text-rewrite';
    }
}

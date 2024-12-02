<?php

namespace Nerd\Nerdai\Classes\inference;

use Nerd\NerdAI\Classes\Prompts\PromptBuilder;

class InferTextSummarization extends InferText
{
    public $mode;
    public function __construct()
    {
        $this->mode = 'text-summarization';
    }
}

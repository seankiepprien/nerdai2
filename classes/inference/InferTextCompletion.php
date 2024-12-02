<?php

namespace Nerd\Nerdai\Classes\inference;

use Nerd\NerdAI\Classes\Prompts\PromptBuilder;

/**
 * Class InferTextCompletion
 * @package Nerd\Nerdai\Classes\inference
 */
class InferTextCompletion extends inferText
{
    public $mode;
    public function __construct()
    {
        $this->mode = 'text-completion';
    }
}

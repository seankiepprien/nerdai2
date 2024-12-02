<?php

namespace Nerd\Nerdai\Classes\inference;

use Nerd\NerdAI\Classes\Prompts\PromptBuilder;
use Nerd\NerdAI\Classes\InferenceInterface;

/**
 * Class InferText
 * @package Nerd\Nerdai\Classes\inference
 */
abstract class InferText implements InferenceInterface
{
    public $mode;
    public $prompt;
    public function __construct($mode)
    {
        $this->mode = $mode;
    }

    public function makePrompt(string|array $input): string
    {
        $promptBuilder = new PromptBuilder();

        $this->prompt = $promptBuilder->toPrompt($input, $this->mode);

        return $this->prompt;
    }

    public function getResponse(string|array $input, array $options = []): string
    {
        $this->makePrompt($input);

        return "nerd.nerdai::lang.inferText.reach" . $this->mode . '####' . $this->prompt;
    }
}

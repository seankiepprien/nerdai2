<?php

namespace Nerd\Nerdai\Classes\tasks;

use Nerd\Nerdai\Classes\prompts\PromptBuilder;
use Nerd\Nerdai\Classes\TaskInterface;

abstract class BuildTask implements TaskInterface
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

        return "nerd.nerdai::lang.build.reach" . $this->mode . '####' . $this->prompt;
    }
}

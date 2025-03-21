<?php

namespace Nerd\Nerdai\Classes\tasks;

use Log;
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

    public function makePrompt(string|array $input, array $options): string
    {
        $promptBuilder = new PromptBuilder();

        $dealerInfo = $options['dealerInfo'];
        $vehicleInfo = $options['vehicleInfo'];

        $this->prompt = $promptBuilder->toPrompt($input, $this->mode, $dealerInfo, $vehicleInfo);

        return $this->prompt;
    }

    public function getResponse(string|array $input, array $options): array|string
    {
        $this->makePrompt($input, $options);

        return "nerd.nerdai::lang.build.reach" . $this->mode . '####' . $this->prompt;
    }
}

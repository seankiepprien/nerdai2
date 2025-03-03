<?php

namespace Nerd\Nerdai\Classes\prompts;

use Nerd\Nerdai\Classes\prompts\PromptBuilder;

class VisionPromptBuilder extends PromptBuilder
{
    public function toPrompt($value, $mode, $dealerInfo, $vehicleInfo): string
    {
        if (!isset($value['prompt'])) {
            throw new \Exception('Prompt is required for vision analysis');
        }

        $basePrompt = parent::toPrompt($value['prompt'], $mode);

        switch ($mode) {
            case 'vision-analysis':
                return $basePrompt . ' Analyse this image and provide detailed observations.';
            default:
                return $basePrompt;
        }
    }
}

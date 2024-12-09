<?php

namespace Nerd\Nerdai\Classes;

use Exception;
use Nerd\Nerdai\Classes\sentimentanalysis\ContentQualityAnalysis;
use Nerd\Nerdai\Classes\sentimentanalysis\PromptQualityAnalysis;

class QualityAnalysisFactory
{
    public static function createAnalyser(string $type): SentimentAnalysisInterface
    {
        switch ($type) {
            case 'prompt':
                return new PromptQualityAnalysis();
            case 'content':
                return new ContentQualityAnalysis();
            default:
                throw new Exception('Unknown quality analysis type: ' . $type);
        }
    }
}

<?php

namespace Nerd\Nerdai\Classes;

class QualityAnalysisFactory
{
    public static function createAnalyser(string $type): SentimentAnalysisInterface
    {
        switch ($type) {
            case 'prompt':
                return new PromptQualityAnalysis();
            case 'content':
                return new ContentQualityAnalysis();
            case 'sentiment-score':
                return new SentimentScoreQualityAnalysis();
            case 'sentiment-category':
                return new SentimentCategoryQualityAnalysis();
            default:
                throw new Exception('Unknown quality analysis type: ' . $type);
        }
    }
}

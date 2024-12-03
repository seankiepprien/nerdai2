<?php

namespace Nerd\Nerdai\Classes;

interface SentimentAnalysisInterface
{
    /**
     * Analyse the input text and return a sentiment score or analysis.
     *
     * @param string $text The text to analyse.
     * @return mixed The sentiment analysis result (e.g., score or categorized sentiment).
     */
    public function analyze(string $text);
}

<?php namespace Nerd\Nerdai\Classes;

interface InferenceInterface
{
    public function makePrompt($input): string;
    public function getResponse($input, $options = []);
}

<?php namespace Nerd\Nerdai\Classes;

interface InferenceInterface
{
    public function makePrompt(string|array $input): string;
    public function getResponse(string|array $input, array $options = []);
}

<?php

namespace Nerd\Nerdai\Classes;

interface TaskInterface
{
    public function makePrompt(string|array $input, array $options): string;
    public function getResponse(string|array $input, array $options);
}

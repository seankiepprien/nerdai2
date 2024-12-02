<?php

namespace Nerd\Nerdai\Classes\tasks;

class PromptTask extends BuildTask
{
    public function __construct()
    {
        $this->mode = 'text-prompt';
    }
}

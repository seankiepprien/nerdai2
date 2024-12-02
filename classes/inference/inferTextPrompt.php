<?php

namespace Nerd\Nerdai\Classes\inference;

class inferTextPrompt extends InferText
{
    public $mode;
    public function __constuct()
    {
        $this->mode = 'text-prompt';
    }
}

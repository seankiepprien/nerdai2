<?php

namespace Nerd\Nerdai\Classes\tasks;

class CompleteTask extends BuildTask
{
    public function __construct()
    {
        $this->mode = 'text-completion';
    }
}

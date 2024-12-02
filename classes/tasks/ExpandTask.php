<?php

namespace Nerd\Nerdai\Classes\tasks;

class ExpandTask extends BuildTask
{
    public $mode;
    public function __construct()
    {
        $this->mode = 'text-expansion';
    }
}

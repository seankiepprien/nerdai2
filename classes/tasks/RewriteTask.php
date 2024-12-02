<?php

namespace Nerd\Nerdai\Classes\tasks;

class RewriteTask extends BuildTask
{
    public $mode;

    public function __construct()
    {
        $this->mode = 'text-rewrite';
    }
}

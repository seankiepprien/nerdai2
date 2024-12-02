<?php

namespace Nerd\Nerdai\Classes\tasks;

class HtmlCodeTask extends BuildTask
{
    public $mode;

    public function __construct()
    {
        $this->mode = 'html-code';
    }
}

<?php

namespace Nerd\Nerdai\Classes\tasks;

class SummarizeTask extends BuildTask
{
    public function __construct()
    {
        $this->mode = 'text-summarization';
    }
}

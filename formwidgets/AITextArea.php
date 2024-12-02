<?php namespace Nerd\Nerdai\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Log;
use Nerd\Nerdai\Models\Log as AILog;
use Nerd\Nerdai\Classes\Clients\OpenAIClient;
use Nerd\Nerdai\Classes\Models\OpenAI\GPT4;
use Nerd\Nerdai\Classes\tasks\CompleteTask;
use Nerd\Nerdai\Classes\tasks\ExpandTask;
use Nerd\Nerdai\Classes\tasks\PromptTask;
use Nerd\Nerdai\Classes\tasks\RewriteTask;
use Nerd\Nerdai\Classes\tasks\SummarizeTask;
use Nerd\NerdAI\Models\NerdAiSettings as Settings;
use Session;

/**
 * AITextArea Form Widget
 *
 * @link https://docs.octobercms.com/3.x/extend/forms/form-widgets.html
 */
class AITextArea extends FormWidgetBase
{
    protected $defaultAlias = 'nerdai_ai_text_area';

    public function init()
    {
    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('aitextarea');
    }

    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
        $this->vars['eventHandlerRewrite'] = $this->getEventHandler('onRewrite');
        $this->vars['eventHandlerComplete'] = $this->getEventHandler('onComplete');
        $this->vars['eventHandlerSummarize'] = $this->getEventHandler('onSummarize');
        $this->vars['eventHandlerElaborate'] = $this->getEventHandler('onElaborate');
        $this->vars['eventHandlerPrompt'] = $this->getEventHandler('onPrompt');
    }

    public function loadAssets()
    {
        $this->addCss('css/aitextarea.css');
        $this->addJs('js/aitextarea.js');
    }

    public function getSaveValue($value)
    {
        return $value;
    }

    public function onElaborate()
    {
        $prompt = post($this->getFieldName());

        $response = GPT4::query(
            $prompt,
            'elaborate',
            'text-generation',
        );

        $this->prepareVars();
        $this->vars['value'] = $response['result'];

        return ['#'.$this->getId() => $this->makePartial('aitextarea')];
    }

    public function onRewrite()
    {
        $prompt = post($this->getFieldName());

        $response = GPT4::query(
            $prompt,
            'rewrite',
            'text-generation'
        );

        $this->prepareVars();
        $this->vars['value'] = $response['result'];

        return ['#'.$this->getId() => $this->makePartial('aitextarea')];
    }

    public function onComplete()
    {
        $prompt = post($this->getFieldName());

        $response = GPT4::query(
            $prompt,
            'complete',
            'text-generation'
        );

        $this->prepareVars();
        $this->vars['value'] = $response['result'];

        return ['#'.$this->getId() => $this->makePartial('aitextarea')];
    }

    public function onSummarize()
    {
        $prompt = post($this->getFieldName());

        $response = GPT4::query(
            $prompt,
            'summarize',
            'text-generation'
        );

        $this->prepareVars();
        $this->vars['value'] = $response['result'];

        return ['#'.$this->getId() => $this->makePartial('aitextarea')];
    }

    public function onPrompt()
    {
        $prompt = post($this->getFieldName());

        $response = GPT4::query(
            $prompt,
            'prompt',
            'text-generation'
        );

        $this->prepareVars();
        $this->vars['value'] = $response['result'];

        return ['#'.$this->getId() => $this->makePartial('aitextarea')];
    }

    public function onGenerateAIResponse()
    {
        $prompt = post('aiPrompt');

        if (!$prompt) {
            throw new \ValidationException(['aiPrompt' => 'Please enter a prompt.']);
        }

        $response = GPT4::query(
            $prompt,
            'html-code',
            'text-generation'
        );

        Session::put('logID', $response['logID']);

        return ['result' => $response['result']];
    }

    public function onAddAIToEditor()
    {
        $aiResponse = post('aiResponse');

        if (!$aiResponse) {
            throw new \ValidationException(['aiResponse' => 'AI is not responding, please try again.']);
        }

        $LogID = Session::get('logID');

        $log = AILog::find($LogID);

        if ($log) {
            $log->taken_prompt = true;
            $log->save();
        }

        return [
            'result' => $aiResponse,
            'context' => 'onAddAIToEditor'
        ];
    }
}

<?php namespace Nerd\Nerdai\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Log;
use Nerd\Nerdai\Classes\Models\OpenAI\gpt4;

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

    protected function processAIRequest(string $prompt, string $task): array
    {
        try {
            $response = gpt4::query(
                $prompt,
                $task,
                'text-generation',
                null
            );

            $this->prepareVars();
            $this->vars['value'] = $response['result'];

            return [
                '#'.$this->getId() => $this->makePartial('aitextarea'),
                'success' => true
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage(), [
                'task' => $task,
                'prompt' => $prompt,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'error' => $e->getMessage(),
                'success' => false
            ];
        }
    }

    public function onElaborate()
    {
        $prompt = post($this->getFieldName());
        return $this->processAIRequest($prompt, 'elaborate');
    }

    public function onRewrite()
    {
        $prompt = post($this->getFieldName());
        return $this->processAIRequest($prompt, 'rewrite');
    }

    public function onComplete()
    {
        $prompt = post($this->getFieldName());
        return $this->processAIRequest($prompt, 'complete');
    }

    public function onSummarize()
    {
        $prompt = post($this->getFieldName());
        return $this->processAIRequest($prompt, 'summarize');
    }

    public function onPrompt()
    {
        $prompt = post($this->getFieldName());
        return $this->processAIRequest($prompt, 'prompt');
    }
}

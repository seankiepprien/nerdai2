<?php namespace Nerd\Nerdai\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Log;
use Nerd\Nerdai\Classes\Clients\OpenAIClient;
use Nerd\NerdAI\Classes\Inference\InferTextPrompt;
use Nerd\NerdAI\Classes\Inference\InferTextRewrite;
use Nerd\Nerdai\Classes\Models\OpenAI\Gpt35TurboInstruct;
use Nerd\Nerdai\Models\AiModel;
use Nerd\NerdAI\Models\NerdAiSettings as Settings;
use Nerd\NerdAI\Classes\Inference\InferTextCompletion;
use Nerd\NerdAI\Classes\Inference\InferTextSummarization;

/**
 * AIText Form Widget
 *
 * @link https://docs.octobercms.com/3.x/extend/forms/form-widgets.html
 */
class AIText extends FormWidgetBase
{
    protected $allowedTasks = [
        'text-completion',
        'text-summarization',
        'text-rewrite',
        'chat'
    ];

    protected $defaultAlias = 'nerdai_ai_text';

    public function init()
    {
    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('aitext');
    }

    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
        $this->vars['eventHandlerOnInferTextCompletion'] = $this->getEventHandler('onInferTextCompletion');
        $this->vars['eventHandlerOnInferTextSummarization'] = $this->getEventHandler('onInferTextSummarization');
        $this->vars['eventHandlerOnInferTextRewrite'] = $this->getEventHandler('onInferTextRewrite');
        $this->vars['eventHandlerOnInferTextPrompt'] = $this->getEventHandler('onInferTextPrompt');
    }

    public function loadAssets()
    {
    }

    public function getSaveValue($value)
    {
        return $value;
    }

    public function onInferTextCompletion()
    {
        $input = post($this->getFieldName());

        $apiKey = Settings::get('openai_api_key');
        $organization = Settings::get('openai_api_organization');
        $parameters = [
            'model' => AIModel::get('model_aimodel')->first(),
        ];
        $client = new OpenAIClient($apiKey, $organization, $parameters);

        $model = AIModel::get('model_aimodel')->first();

        $inference = new InferTextCompletion();
        $response = $inference->getResponse($input);

        Log::info($response);

        $mode = 'chat';

        $aiResponse = Gpt35TurboInstruct::query(
            $model,
            $client,
            $response,
            $parameters,
            $mode
        );

        $this->prepareVars();


        $this->vars['value'] = $aiResponse['result'];

        return ['#'.$this->getId() => $this->makePartial('aitext')];
    }

    public function onInferTextSummarization()
    {
        $input = post($this->getFieldName());

        $inference = new InferTextSummarization();
        $response = $inference->getResponse($input);

        $this->prepareVars();
        $this->vars['value'] = $response;

        return ['#'.$this->getId() => $this->makePartial('aitext')];
    }

    public function onInferTextRewrite()
    {
        $input = post($this->getFieldName());
        $rewriteMode = post('mode')? 'text-rewrite-expand' : 'text-rewrite';

        $inference = new InferTextRewrite($rewriteMode);
        $response = $inference->getResponse($input);

        $this->prepareVars();
        $this->vars['value'] = $response;

        return ['#'.$this->getId() => $this->makePartial('aitext')];
    }

    public function onInferTextPrompt()
    {
        $input = post($this->getFieldName());

        $inference = new InferTextPrompt();
        $response = $inference->getResponse($input);

        $this->prepareVars();
        $this->vars['value'] = $response;

        return ['#'.$this->getId() => $this->makePartial('aitext')];
    }
}

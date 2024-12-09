<?php namespace Nerd\Nerdai\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Backend\Facades\BackendAuth;
use Backend\FormWidgets\RichEditor;
use Backend\Models\EditorSetting;
use Nerd\Nerdai\Classes\Models\OpenAI\GPT4;
use Nerd\Nerdai\Models\Log as AILog;
use Request;
use Session;

/**
 * AIRichEditor Form Widget
 *
 * @link https://docs.octobercms.com/3.x/extend/forms/form-widgets.html
 */
class AIRichEditor extends RichEditor
{
    protected $defaultAlias = 'nerdai_a_i_rich_editor';

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('./modules/backend/formwidgets/richeditor/partials/_richeditor.php');
    }

    public function loadAssets()
    {
        $this->addCss('css/airicheditor.css');
        $this->addJs('js/airicheditor.js');
    }

    public function getSaveValue($value)
    {
        return $value;
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

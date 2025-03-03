<?php namespace Nerd\Nerdai\FormWidgets;

use ApplicationException;
use Backend\Classes\FormWidgetBase;
use Exception;
use http\Client\Request;
use Input;
use Log;
use Nerd\Nerdai\Models\NerdAiSettings;

/**
 * AIFileUpload Form Widget
 *
 * @link https://docs.octobercms.com/3.x/extend/forms/form-widgets.html
 */
class AIFileUpload extends FormWidgetBase
{
    protected $defaultAlias = 'nerdai_a_i_file_upload';

    public function init()
    {
        $this->fillFromConfig([
            'prompt',
        ]);
    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('aifileupload');
    }

    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
        $this->vars['prompt'] = $this->prompt ?? 'Analyze this image and describe what you see.';
    }

    public function loadAssets()
    {
        $this->addCss('css/aifileupload.css');
        $this->addJs('js/aifileupload.js');
    }

    public function getSaveValue($value)
    {
        return $value;
    }

    public function onAnalyzeImage()
    {
        try {
            $settings = NerdAiSettings::instance();
            $image = $settings->test_image;

            if (!$image) {
                throw new Exception('No image uploaded.');
            }

            // Convert image to base64
            $imageData = base64_encode(file_get_contents($image->getLocalPath()));
            $base64Image = 'data:' . $image->content_type . ';base64,' . $imageData;

            $input = [
                'image' => $base64Image,
                'prompt' => 'Analyze this image and describe what you see.'
            ];

            $response = \Nerd\Nerdai\Classes\Models\OpenAI\GPT4::query(
                $input,
                'vision',
                'vision-analysis',
                null
            );

            return [
                'result' => $response['result']
            ];
        } catch (Exception $e) {
            Log::error('Vision analysis failed: ' . $e->getMessage());
            throw new ApplicationException($e->getMessage());
        }
    }
}

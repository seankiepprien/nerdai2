<?php namespace Nerd\Nerdai\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * AIRichEditor Form Widget
 *
 * @link https://docs.octobercms.com/3.x/extend/forms/form-widgets.html
 */
class AIRichEditor extends FormWidgetBase
{
    protected $defaultAlias = 'nerdai_a_i_rich_editor';

    public function init()
    {

    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('airicheditor');
    }

    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
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
}

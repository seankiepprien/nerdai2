<?php namespace Nerd\Nerdai\Models;

use System\Models\SettingModel;

/**
 * NerdAiSettings Model
 */
class NerdAiSettings extends SettingModel
{
    use \October\Rain\Database\Traits\Validation;

    public $rules = [];

    public $settingsCode = 'nerd_ai_settings';
    public $settingsFields = 'fields.yaml';
}

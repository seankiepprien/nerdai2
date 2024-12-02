<?php namespace Nerd\Nerdai\Models;

use Model;

/**
 * NerdAiSettings Model
 */
class NerdAiSettings extends Model
{
    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    public $settingsCode = 'nerd_ai_settings';

    public $settingsFields = 'fields.yaml';

}

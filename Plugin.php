<?php namespace Nerd\Nerdai;

use Backend;
use Event;
use Parsedown;
use System\Classes\PluginBase;
use Log;

use Nerd\Nerdai\Models\NerdAiSettings;

/**
 * nerdai Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'nerdai',
            'description' => 'No description provided yet...',
            'author'      => 'nerd',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        \Backend\FormWidgets\RichEditor::extend(function($controller) {
            $controller->addJs('/plugins/nerd/nerdai/formwidgets/aitextarea/assets/js/aitextarea.js');
        });

        Event::listen('backend.ajax.beforeRunHandler', function ($controller, $handler) {
            if ($handler === 'onLoadPopup') {
                return $controller->makePartial('$/nerd/nerdai/formwidgets/airicheditor/partials/_popup.php');
            }
            if ($handler == 'onAnalyzeImage') {
                return \Nerd\Nerdai\Models\NerdAiSettings::onAnalyzeImage();
            }
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Nerd\Nerdai\Components\AssistantChat' => 'assistantChat'
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'nerd.nerdai.some_permission' => [
                'tab' => 'nerdai',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'nerdai' => [
                'label'       => 'nerdai',
                'icon'        => 'icon-leaf',
                'permissions' => ['nerd.nerdai.*'],
                'order'       => 500,
                'sideMenu'    => [
                    'assistant' => [
                        'label'       => 'Assistant',
                        'icon'        => 'icon-comment',
                        'url'         => Backend::url('nerd/nerdai/assistants'),
                        'permissions' => ['nerd.nerdai.assistant'],
                    ],
                    'logs' => [
                        'label'       => 'Logs',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('nerd/nerdai/logs'),
                        'permissions' => ['nerd.nerdai.logs'],
                    ],
                    'settings' => [
                        'label'       => 'Settings',
                        'icon'        => 'icon-cogs',
                        'url'         => Backend::url('system/settings/update/nerd/nerdai/settings'),
                        'permissions' => ['nerd.nerdai.settings'],
                    ]
                ]
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'NerdAI Settings',
                'description' => 'Settings for the NerdAI plugin',
                'category' => 'NerdAI',
                'icon' => ' icon-cogs',
                'class' => NerdAiSettings::class,
                'order' => 500,
                'keywords' => 'nerdai ai',
            ]
        ];
    }

    public function registerFormWidgets()
    {
        return [
            \Nerd\Nerdai\FormWidgets\AIText::class => 'aitext',
            \Nerd\Nerdai\FormWidgets\AITextArea::class => 'aitextarea',
            \Nerd\Nerdai\FormWidgets\AIRichEditor::class => 'airicheditor',
            \Nerd\Nerdai\FormWidgets\AIFileUpload::class => 'aifileupload',
            \Nerd\Nerdai\FormWidgets\ChatWidget::class => 'chatwidget'
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'markdown' => [$this, 'parseMarkdown']
            ]
        ];
    }

    public function registerAssistantFunctionHandlers()
    {
        return [
            'toyota_steustache' => \Nerd\Nerdai\Classes\AssistantHandlers\ToyotaStEustacheHandlers::class
        ];
    }

    public function parseMarkdown($text)
    {
        $parsedown = new Parsedown();
        return $parsedown->text($text);
    }
}

<?php namespace Nerd\Nerdai;

use Backend;
use System\Classes\PluginBase;

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

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Nerd\Nerdai\Components\MyComponent' => 'myComponent',
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
        return []; // Remove this line to activate

        return [
            'nerdai' => [
                'label'       => 'nerdai',
                'url'         => Backend::url('nerd/nerdai/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['nerd.nerdai.*'],
                'order'       => 500,
            ],
        ];
    }

    public function registerSettings()
    {
        return [];
        return [
            'settings' => [
                'label' => 'NerdAI Settings',
                'description' => 'Settings for the NerdAI plugin',
                'category' => 'NerdAI',
                'icon' => ' icon-cogs',
                'class' => \Nerd\Nerdai\Models\NerdAiSettings,
                'order' => 500,
                'keywords' => 'nerdai ai',
            ]
        ];
    }
}

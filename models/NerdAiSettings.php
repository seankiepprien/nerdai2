<?php namespace Nerd\Nerdai\Models;

use Model;
use Log;

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

    public $attachOne = [
        'test_image' => 'System\Models\File'
    ];

    public static function onAnalyzeImage()
    {
        try {
            $settings = self::instance();
            $imageUrl = post('image_url');

            if (!$imageUrl) {
                throw new \Exception('Please enter an image URL.');
            }

            // Validate URL format
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                throw new \Exception('Please enter a valid URL.');
            }

            $input = [
                'image' => $imageUrl,
                'prompt' => 'Analyze this image and describe what you see.'
            ];

            $response = \Nerd\Nerdai\Classes\Models\OpenAI\GPT4::query(
                $input,
                'vision',
                'vision-analysis',
                null
            );

            return [
                '#analysisResult' => $response['result']
            ];
        } catch (\Exception $e) {
            \Log::error('Vision analysis failed: ' . $e->getMessage());
            throw new \ApplicationException($e->getMessage());
        }
    }

}

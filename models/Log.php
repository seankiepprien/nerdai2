<?php namespace Nerd\Nerdai\Models;

use Model;

/**
 * Log Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Log extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'nerd_nerdai_logs';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $casts = [
        'request' => 'array',
        'response' => 'array'
    ];

    public function logRecord(
        string $model,
        string $task,
        string $mode,
        array $request,
        array $response) {
        $log = new \Nerd\Nerdai\Models\Log;
        $log->model = $model;
        $log->task = $task;
        $log->mode = $mode;
        $log->request = $request;
        $log->response = $response;
        $log->save();

        return $log;
    }
}

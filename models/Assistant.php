<?php namespace Nerd\Nerdai\Models;

use Model;
use October\Rain\Database\Collection;

/**
 * Assistant Model
 *
 * @property string $name
 * @property string $assistant_id
 * @property string $description
 * @property string $instructions
 * @property string $model
 * @property array $tools
 * @property bool $is_active
 */
class Assistant extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'nerd_nerdai_assistants';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|string|max:255',
        'assistant_id' => 'required|string|max:255',
        'instruction' => 'required|string',
        'model' => 'required|string|max:100',
    ];

    protected $jsonable = ['tools'];

    protected $guarded = ['*'];

    protected $fillable = [
        'name',
        'assistant_id',
        'description',
        'instructions',
        'model',
        'tools',
        'is_active'
    ];

    /**
     * Find assistant by external assistant_id
     *
     * @param string $assistantId
     * @return Assistant|null
     */
    public function findByAssistantId(string $assistantId)
    {
        return self::where('assistant_id', $assistantId)->first();
    }

    /**
     * Get active assistants
     *
     * @return Collection
     */
    public static function getActive()
    {
        return self::where('is_active', true)->get();
    }

    public $hasMany = [
        'threads' => [
            'Nerd\Nerdai\Models\Thread',
            'key' => 'assistant_id',
            'otherKey' => 'id'
        ]
    ];
}

<?php namespace Nerd\Nerdai\Models;

use Model;

/**
 * Thread Model
 *
 * @property string $thread_id
 * @property int $assistant_id
 * @property string $title
 * @property string $description
 * @property array $metadata
 * @property bool $is_active
 * @property \Nerd\Nerdai\Models\Assistant $assistant
 */
class Thread extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'nerd_nerdai_threads';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'thread_id' => 'required|string|max:255',
        'assistant_id' => 'required|exists:nerd_nerdai_assistants,id',
    ];

    protected $jsonable = ['metadata'];

    protected $guarded = ['*'];

    protected $fillable = [
        'thread_id',
        'assistant_id',
        'title',
        'description',
        'metadata',
        'is_active'
    ];

    /**
     * Find thread by external thread_id
     *
     * @param string $threadId
     * @return Thread|null
     */
    public static function findByThreadId(string $threadId)
    {
        return self::where('thread_id', $threadId)->first();
    }

    public $belongsTo = [
        'assistant' => [
            'Nerd\Nerdai\Models\Assistant',
            'key' => 'assistant_id'
        ]
    ];

    public $hasMany = [
        'messages' => [
            'Nerd\Nerdai\Models\Message',
            'key' => 'thread_id',
            'otherKey' => 'id'
        ]
    ];
}

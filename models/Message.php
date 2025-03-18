<?php namespace Nerd\Nerdai\Models;

use Model;

/**
 * Message Model
 *
 * @property string $message_id
 * @property int $thread_id
 * @property string $role
 * @property string $content
 * @property array $metadata
 * @property \Nerd\Nerdai\Models\Thread $thread
 */
class Message extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'nerd_nerdai_messages';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'message_id' => 'required|string|max:255',
        'thread_id' => 'required|exists:nerd_nerdai_threads,id',
        'role' => 'required|string|in:user,assistant,system',
        'content' => 'required|string',
    ];

    protected $jsonable = ['metadata'];

    protected $guarded = ['*'];

    protected $fillable = [
        'message_id',
        'thread_id',
        'role',
        'content',
        'metadata'
    ];

    /**
     * Find a message by external message_id
     *
     * @param string $messageId
     * @return Message|null
     */
    public static function findByMessageId(string $messageId)
    {
        return self::where('message_id', $messageId)->first();
    }

    public $belongsTo = [
        'thread' => [
            'Nerd\Nerdai\Models\Thread',
            'key' => 'thread_id'
        ]
    ];
}

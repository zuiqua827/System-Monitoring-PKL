<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $admin_name
 * @property string $status
 * @property int $added
 * @property int $updated
* @property int $deleted
 * @property int $skipped
 * @property int $teacher_added
 * @property int $teacher_updated
 * @property int $teacher_deleted
 * @property int $teacher_skipped
 * @property int $duration_ms
 * @property string|null $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $admin
 */
class SiPintuSyncLog extends Model
{
    protected $table = 'sipintu_sync_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'admin_name',
        'status',
        'added',
        'updated',
'deleted',
        'skipped',
        'teacher_added',
        'teacher_updated',
        'teacher_deleted',
        'teacher_skipped',
        'duration_ms',
        'message',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'added' => 'integer',
            'updated' => 'integer',
'deleted' => 'integer',
            'skipped' => 'integer',
            'teacher_added' => 'integer',
            'teacher_updated' => 'integer',
            'teacher_deleted' => 'integer',
            'teacher_skipped' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

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
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property string $status
 * @property string $entity_scope
 * @property int $remote_total
 * @property int $added
 * @property int $updated
 * @property int $deleted
 * @property int $skipped
 * @property int $unchanged
 * @property int $needs_mapping
 * @property int $conflicts
 * @property int $duplicates
 * @property int $invalid
 * @property int $failed
 * @property int $teacher_added
 * @property int $teacher_updated
 * @property int $teacher_deleted
 * @property int $teacher_skipped
 * @property int $dudi_added
 * @property int $dudi_updated
 * @property int $dudi_deleted
 * @property int $dudi_skipped
 * @property int $duration_ms
 * @property array<string, mixed>|null $summary
 * @property array<int|string, mixed>|null $error_details
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
        'started_at',
        'finished_at',
        'status',
        'entity_scope',
        'remote_total',
        'added',
        'updated',
        'deleted',
        'skipped',
        'unchanged',
        'needs_mapping',
        'conflicts',
        'duplicates',
        'invalid',
        'failed',
        'teacher_added',
        'teacher_updated',
        'teacher_deleted',
        'teacher_skipped',
        'dudi_added',
        'dudi_updated',
        'dudi_deleted',
        'dudi_skipped',
        'duration_ms',
        'summary',
        'error_details',
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
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'remote_total' => 'integer',
            'added' => 'integer',
            'updated' => 'integer',
            'deleted' => 'integer',
            'skipped' => 'integer',
            'unchanged' => 'integer',
            'needs_mapping' => 'integer',
            'conflicts' => 'integer',
            'duplicates' => 'integer',
            'invalid' => 'integer',
            'failed' => 'integer',
            'teacher_added' => 'integer',
            'teacher_updated' => 'integer',
            'teacher_deleted' => 'integer',
            'teacher_skipped' => 'integer',
            'dudi_added' => 'integer',
            'dudi_updated' => 'integer',
            'dudi_deleted' => 'integer',
            'dudi_skipped' => 'integer',
            'duration_ms' => 'integer',
            'summary' => 'array',
            'error_details' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

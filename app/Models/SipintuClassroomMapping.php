<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Maps a SiPintu opaque classroom_id to a local kelas record.
 *
 * One classroom_id maps to exactly one local kelas (UNIQUE constraint).
 * This mapping is created by the Super Admin via the Admin mapping page and
 * is reused automatically by all future SiPintu synchronizations.
 *
 * @property int $id
 * @property int $classroom_id
 * @property int $kelas_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Kelas $kelas
 */
class SipintuClassroomMapping extends Model
{
    protected $table = 'sipintu_classroom_mappings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'classroom_id',
        'kelas_id',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'classroom_id' => 'integer',
            'kelas_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Kelas, $this> */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
}

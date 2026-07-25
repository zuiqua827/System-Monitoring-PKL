<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\KomentarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $aktivitas_id
 * @property string $isi
 * @property bool $is_internal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Aktivitas $aktivitas
 * @property-read Guru|null $guru
 */
class Komentar extends Model
{
    /** @use HasFactory<KomentarFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'komentar';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'aktivitas_id',
        'isi',
        'is_internal',
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
            'aktivitas_id' => 'integer',
            'is_internal' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function aktivitas(): BelongsTo
    {
        return $this->belongsTo(Aktivitas::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }
}

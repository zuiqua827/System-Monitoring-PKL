<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit trail record for changes to a PenempatanPKL work mode & geofence settings.
 *
 * @property int $id
 * @property int $penempatan_pkl_id
 * @property int|null $changed_by
 * @property string $changed_by_name
 * @property string|null $changed_by_role
 * @property array<string, mixed> $old_values
 * @property array<string, mixed> $new_values
 * @property string|null $reason
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PenempatanPKL $penempatanPKL
 * @property-read User|null $changer
 */
class PenempatanPKLLocationLog extends Model
{
    protected $table = 'penempatan_pkl_location_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'penempatan_pkl_id',
        'changed_by',
        'changed_by_name',
        'changed_by_role',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'penempatan_pkl_id' => 'integer',
            'changed_by' => 'integer',
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /** @return BelongsTo<PenempatanPKL, $this> */
    public function penempatanPKL(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

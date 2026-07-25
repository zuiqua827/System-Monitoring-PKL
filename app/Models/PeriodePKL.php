<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PeriodePKLFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nama
 * @property string $tahun_ajaran
 * @property string $semester
 * @property Carbon $tanggal_mulai
 * @property Carbon $tanggal_selesai
 * @property string $status
 * @property string|null $deskripsi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PenempatanPKL> $penempatan
 */
class PeriodePKL extends Model
{
    /** @use HasFactory<PeriodePKLFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'periode_pkl';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'tahun_ajaran',
        'semester',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'deskripsi',
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
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function penempatan(): HasMany
    {
        return $this->hasMany(PenempatanPKL::class);
    }
}

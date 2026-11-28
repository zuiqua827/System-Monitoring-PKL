<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $idempotency_key
 * @property int|null $penempatan_pkl_id
 * @property int|null $siswa_id
 * @property int|null $user_id
 * @property string $recipient_phone
 * @property string $message_type
 * @property string $message_content
 * @property string $status
 * @property string|null $error_reason
 * @property array<string, mixed>|null $response_payload
 * @property Carbon $tanggal
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PenempatanPKL|null $penempatan
 * @property-read Siswa|null $siswa
 * @property-read User|null $user
 */
class WhatsAppLog extends Model
{

    protected $table = 'whatsapp_logs';

    // ============================================================
    // STATUS SIKLUS PENGIRIMAN NOTIFIKASI WHATSAPP
    // ============================================================
    // 1. pending : Pesan telah dijadwalkan dan masuk antrean Queue.
    // 2. sending : Sedang dalam proses transmisi HTTP ke Gateway Port 3005.
    // 3. sent    : Berhasil terkirim ke WhatsApp penerima.
    // 4. failed  : Gagal setelah mencoba berulang (retry exhausted) atau error fatal.
    // 5. skipped : Dilewati (misal: nomor tidak valid, fitur dinonaktifkan).
    //
    // Kolom 'idempotency_key' menjamin pesan pengingat tidak dikirimkan
    // lebih dari satu kali untuk siswa dan tanggal yang sama.
    // ============================================================

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public const TYPE_ATTENDANCE_REMINDER = 'attendance_warning';
    public const TYPE_ATTENDANCE_LATE = 'attendance_late';
    public const TYPE_MANUAL_TEST = 'manual_test';


    /**
     * @var list<string>
     */
    protected $fillable = [
        'idempotency_key',
        'penempatan_pkl_id',
        'siswa_id',
        'user_id',
        'recipient_phone',
        'message_type',
        'message_content',
        'status',
        'error_reason',
        'response_payload',
        'tanggal',
        'sent_at',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'penempatan_pkl_id' => 'integer',
            'siswa_id' => 'integer',
            'user_id' => 'integer',
            'response_payload' => 'array',
            'tanggal' => 'date',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * Generate standard SHA-256 idempotency key for a given student, date, and notification type.
     */
    public static function generateIdempotencyKey(int $siswaId, string $date, string $type): string
    {
        return hash('sha256', "wa_notif:{$siswaId}:{$date}:{$type}");
    }

    /**
     * Concurrency-safe firstOrCreate using unique constraint.
     * Catches race-condition QueryExceptions on duplicate keys and retrieves existing record.
     *
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     * @return WhatsAppLog
     */
    public static function firstOrCreateSafe(array $attributes, array $values = []): WhatsAppLog
    {
        try {
            /** @var WhatsAppLog $log */
            $log = static::firstOrCreate($attributes, $values);
            return $log;
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = (string) $e->getCode();
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            $message = $e->getMessage();

            if (
                $sqlState === '23000' ||
                $errorCode === '23000' ||
                str_contains($message, 'Duplicate entry') ||
                str_contains($message, 'UNIQUE constraint failed')
            ) {
                /** @var static $existing */
                $existing = static::where($attributes)->firstOrFail();
                $existing->wasRecentlyCreated = false;
                return $existing;
            }

            throw $e;
        }
    }

    /**
     * @return BelongsTo<PenempatanPKL, $this>
     */
    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id');
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope query to only successfully sent messages.
     *
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope query for a specific notification type on a date.
     *
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeForDateAndType(Builder $query, string $date, string $type): Builder
    {
        return $query->whereDate('tanggal', $date)->where('message_type', $type);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\Jurusan;
use App\Models\Kelas;

/**
 * Single source of truth for extracting SiPintu classroom metadata and performing
 * strict, ambiguity-safe matching against local Kelas records.
 */
final class SipintuClassroomMatcher
{
    /**
     * Determine whether a remote student payload represents an alumni/graduated student.
     *
     * @param  array<string, mixed>  $remote
     */
    public static function isAlumni(array $remote): bool
    {
        if (($remote['graduated'] ?? false) === true) {
            return true;
        }

        $status = $remote['status'] ?? null;
        if ($status === 2 || $status === '2') {
            return true;
        }

        if (is_string($status)) {
            $normalized = strtolower(trim($status));
            if ($normalized === 'alumni' || $normalized === 'lulus') {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the numeric SiPintu classroom_id from a remote student payload.
     * Graduated/alumni students without an active classroom_id return 0.
     *
     * @param  array<string, mixed>  $remote
     */
    public static function extractClassroomId(array $remote): int
    {
        $rawClassroomId = $remote['classroom_id'] ?? $remote['class_id'] ?? $remote['kelas_id'] ?? null;
        if (is_int($rawClassroomId) || (is_string($rawClassroomId) && ctype_digit(trim($rawClassroomId)))) {
            $id = (int) $rawClassroomId;
            if ($id > 0) {
                return $id;
            }
        }

        if (self::isAlumni($remote)) {
            return 0;
        }

        $objId = is_array($remote['classroom'] ?? null) ? ($remote['classroom']['id'] ?? null) : null;
        if (is_int($objId) || (is_string($objId) && ctype_digit(trim($objId)))) {
            return max(0, (int) $objId);
        }

        return 0;
    }

    /**
     * Extract classroom name from supported remote payload keys.
     * Returns null if not provided — never fabricates a class name.
     *
     * @param  array<string, mixed>  $remote
     */
    public static function extractClassroomName(array $remote): ?string
    {
        $classroom = $remote['classroom'] ?? null;
        if (is_array($classroom) && isset($classroom['name']) && is_string($classroom['name'])) {
            $name = trim($classroom['name']);
            if ($name !== '') {
                return $name;
            }
        }

        foreach (['classroom_name', 'kelas', 'rombel'] as $key) {
            if (isset($remote[$key]) && is_string($remote[$key])) {
                $name = trim($remote[$key]);
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return null;
    }

    public static function normalizeName(string $name): string
    {
        return strtoupper(trim((string) preg_replace('/\s+/', ' ', $name)));
    }

    public static function parseTingkat(mixed $value): ?int
    {
        if (is_int($value) && in_array($value, [10, 11, 12], true)) {
            return $value;
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        return match (strtoupper(trim((string) $value))) {
            '10', 'X' => 10,
            '11', 'XI' => 11,
            '12', 'XII' => 12,
            default => null,
        };
    }

    /**
     * Extract grade level (tingkat: 10, 11, 12) from explicit remote fields or class name prefix.
     *
     * @param  array<string, mixed>  $remote
     */
    public static function extractRemoteTingkat(array $remote, ?string $classroomName = null): ?int
    {
        $classroom = is_array($remote['classroom'] ?? null) ? $remote['classroom'] : [];
        $explicit = $remote['tingkat'] ?? $remote['grade'] ?? $classroom['tingkat'] ?? $classroom['grade'] ?? null;

        if ($explicit !== null && $explicit !== '') {
            $parsed = self::parseTingkat($explicit);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        $name = $classroomName ?? self::extractClassroomName($remote);
        if ($name === null) {
            return null;
        }

        if (preg_match('/^\s*(XII|XI|X|10|11|12)(?:\s|$|-)/i', $name, $matches) === 1) {
            return self::parseTingkat($matches[1]);
        }

        return null;
    }

    /**
     * Extract department/jurusan identifier from explicit remote fields or class name token.
     *
     * @param  array<string, mixed>  $remote
     */
    public static function extractRemoteJurusan(array $remote, ?string $classroomName = null): ?string
    {
        $classroom = is_array($remote['classroom'] ?? null) ? $remote['classroom'] : [];
        $explicit = $remote['jurusan']
            ?? $classroom['jurusan']
            ?? $classroom['major']
            ?? $remote['major']
            ?? $remote['department']
            ?? null;

        if (is_array($explicit)) {
            $val = $explicit['kode'] ?? $explicit['code'] ?? $explicit['nama'] ?? $explicit['name'] ?? null;
            if (is_string($val) && trim($val) !== '') {
                return trim($val);
            }
        } elseif (is_string($explicit) && trim($explicit) !== '') {
            return trim($explicit);
        }

        $name = $classroomName ?? self::extractClassroomName($remote);
        if ($name === null) {
            return null;
        }

        // Parse "<Tingkat> <Jurusan> <RombelNumber>" e.g. "XII PPLG 1" -> "PPLG".
        $stripped = preg_replace('/^\s*(?:XII|XI|X|10|11|12)\s+/i', '', $name) ?? $name;
        $stripped = preg_replace('/[\s-]+\d+\s*$/', '', $stripped) ?? $stripped;
        $candidate = trim($stripped);

        if ($candidate !== '' && ! ctype_digit($candidate)) {
            return $candidate;
        }

        return null;
    }

    public static function jurusanMatches(Kelas $kelas, string $remoteJurusan): bool
    {
        /** @var Jurusan|null $jurusan */
        $jurusan = $kelas->relationLoaded('jurusan') ? $kelas->jurusan : $kelas->jurusan()->first();
        if ($jurusan === null) {
            return false;
        }

        $remoteNorm = self::normalizeName($remoteJurusan);
        $kodeNorm = self::normalizeName((string) $jurusan->kode);
        $namaNorm = self::normalizeName((string) $jurusan->nama);

        return $remoteNorm === $kodeNorm || $remoteNorm === $namaNorm;
    }

    /**
     * Verify whether a local Kelas strictly matches a remote classroom (name, tingkat, jurusan, 1-to-1).
     *
     * @param  array<string, mixed>  $remote
     * @param  array<int, int>  $kelasIdToClassroomId  [local kelas_id => mapped classroom_id]
     * @return array{matched: bool, reason: string}
     */
    public static function matchesKelasStrictly(Kelas $kelas, array $remote, array $kelasIdToClassroomId = []): array
    {
        if ($kelas->trashed()) {
            return ['matched' => false, 'reason' => 'local_kelas_trashed'];
        }

        $classroomName = self::extractClassroomName($remote);
        if ($classroomName === null) {
            return ['matched' => false, 'reason' => 'missing_remote_name'];
        }

        if (self::normalizeName($kelas->nama) !== self::normalizeName($classroomName)) {
            return ['matched' => false, 'reason' => 'name_mismatch'];
        }

        $remoteTingkat = self::extractRemoteTingkat($remote, $classroomName);
        if ($remoteTingkat !== null && (int) $kelas->tingkat !== $remoteTingkat) {
            return ['matched' => false, 'reason' => 'tingkat_mismatch'];
        }

        $remoteJurusan = self::extractRemoteJurusan($remote, $classroomName);
        if ($remoteJurusan !== null && ! self::jurusanMatches($kelas, $remoteJurusan)) {
            return ['matched' => false, 'reason' => 'jurusan_mismatch'];
        }

        $classroomId = self::extractClassroomId($remote);
        if ($classroomId > 0
            && isset($kelasIdToClassroomId[$kelas->id])
            && (int) $kelasIdToClassroomId[$kelas->id] !== $classroomId) {
            return ['matched' => false, 'reason' => 'kelas_already_mapped_to_another_classroom'];
        }

        return ['matched' => true, 'reason' => 'exact_match'];
    }

    /**
     * Resolve a single unambiguous local Kelas match for a remote record.
     * Rejects mismatched tingkat/jurusan, conflicts, and ambiguous multiple matches.
     *
     * @param  array<string, mixed>  $remote
     * @param  iterable<int, Kelas>  $localKelasList
     * @param  array<int, int>  $kelasIdToClassroomId
     * @return array{kelas: Kelas|null, reason: string}
     */
    public static function findUniqueStrictMatch(array $remote, iterable $localKelasList, array $kelasIdToClassroomId = []): array
    {
        $classroomName = self::extractClassroomName($remote);
        if ($classroomName === null) {
            return ['kelas' => null, 'reason' => 'missing_remote_name'];
        }

        $normTarget = self::normalizeName($classroomName);
        $nameMatches = [];

        foreach ($localKelasList as $kelas) {
            if (! $kelas->trashed() && self::normalizeName($kelas->nama) === $normTarget) {
                $nameMatches[] = $kelas;
            }
        }

        if ($nameMatches === []) {
            return ['kelas' => null, 'reason' => 'no_name_match'];
        }

        $strictMatches = [];
        $firstFailureReason = 'mismatch';

        foreach ($nameMatches as $candidate) {
            $check = self::matchesKelasStrictly($candidate, $remote, $kelasIdToClassroomId);
            if ($check['matched']) {
                $strictMatches[] = $candidate;
            } elseif ($firstFailureReason === 'mismatch') {
                $firstFailureReason = $check['reason'];
            }
        }

        if ($strictMatches === []) {
            return ['kelas' => null, 'reason' => $firstFailureReason];
        }

        if (count($strictMatches) > 1) {
            return ['kelas' => null, 'reason' => 'ambiguous_multiple_matches'];
        }

        return ['kelas' => $strictMatches[0], 'reason' => 'exact_match'];
    }
}

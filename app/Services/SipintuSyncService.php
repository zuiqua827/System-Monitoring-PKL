<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SiPintuApiException;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\SipintuClassroomMapping;
use App\Models\SiPintuSyncLog;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Repositories\Interfaces\SipintuSyncLogRepositoryInterface;
use App\Services\Interfaces\SiPintuServiceInterface;
use App\Services\Interfaces\SipintuSyncServiceInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service layer for the Super Admin "Sinkronisasi SiPintu" feature.
 *
 * Provides:
 *  - getDashboardData(): non-blocking metrics from local DB & cached remote probes.
 *  - preview(): READ-ONLY classification without any database mutations.
 *  - runSync(): atomic per-record synchronization with full audit trail logging.
 */
class SipintuSyncService extends Service implements SipintuSyncServiceInterface
{
    public function __construct(
        private readonly SiPintuServiceInterface $siPintuService,
        private readonly SipintuSyncLogRepositoryInterface $syncLogRepository,
    ) {}

    public function getDashboardData(): array
    {
        /** @var SiPintuSyncLog|null $lastLog */
        $lastLog = $this->syncLogRepository->latestLog();

        $localStudents = Siswa::query()->withoutTrashed()->count();
        $localTeachers = Guru::query()->withoutTrashed()->count();
        $localDudis = Dudi::query()->withoutTrashed()->count();
        $classroomMappingCount = SipintuClassroomMapping::query()->count();

        $rawCachedCounts = Cache::get('sipintu_dashboard_remote_counts', []);
        $cachedCounts = is_array($rawCachedCounts) ? $rawCachedCounts : [];

        $sipintuStudentCount = array_key_exists('students', $cachedCounts)
            ? (is_numeric($cachedCounts['students']) ? (int) $cachedCounts['students'] : null)
            : ($lastLog !== null && in_array($lastLog->entity_scope, ['all', 'siswa'], true) && $lastLog->remote_total > 0 ? $lastLog->remote_total : null);

        $sipintuTeacherCount = isset($cachedCounts['teachers']) && is_numeric($cachedCounts['teachers'])
            ? (int) $cachedCounts['teachers']
            : null;

        $sipintuDudiCount = isset($cachedCounts['dudis']) && is_numeric($cachedCounts['dudis'])
            ? (int) $cachedCounts['dudis']
            : null;

        $needsMappingCount = isset($cachedCounts['needs_mapping_students']) && is_numeric($cachedCounts['needs_mapping_students'])
            ? (int) $cachedCounts['needs_mapping_students']
            : ($lastLog !== null ? (int) $lastLog->needs_mapping : 0);

        $isConfigured = $this->isSipintuConfigured();

        $status = $isConfigured ? 'ready' : 'not_configured';
        $message = $isConfigured
            ? 'Konfigurasi kredensial SiPintu telah terpasang. Diagnosis endpoint dijalankan secara berkala (cache 5 menit); klik "Test Connection" untuk menyegarkan sekarang.'
            : 'Konfigurasi SiPintu belum lengkap. Silakan atur SIPINTU_API_URL, SIPINTU_CLIENT_ID, dan SIPINTU_CLIENT_SECRET di file .env.';

        return [
            'connection_status' => $status,
            'connection_message' => $message,
            'connection_detail' => null,
            'connection_troubleshooting' => $isConfigured ? null : 'Buka file .env dan atur kredensial Gateway SiPintu.',
            'connection_success' => $isConfigured,
            'connection_http_status' => null,
            'connection_error_type' => $isConfigured ? null : 'configuration',
            'last_sync' => $lastLog ? $this->serializeLog($lastLog) : null,
            'sipintu_student_count' => $sipintuStudentCount,
            'sipintu_teacher_count' => $sipintuTeacherCount,
            'sipintu_dudi_count' => $sipintuDudiCount,
            'local_student_count' => $localStudents,
            'local_teacher_count' => $localTeachers,
            'local_dudi_count' => $localDudis,
            'classroom_mapping_count' => $classroomMappingCount,
            'needs_mapping_count' => $needsMappingCount,
            'history' => $this->syncLogRepository->paginateHistory(15),
        ];
    }

    private function isSipintuConfigured(): bool
    {
        $apiUrl = config('services.sipintu.api_url');
        $clientId = config('services.sipintu.client_id');
        $clientSecret = config('services.sipintu.client_secret');
        $apiToken = config('services.sipintu.api_token');

        return ! empty($apiUrl) && (! empty($apiToken) || (! empty($clientId) && ! empty($clientSecret)));
    }

    /**
     * {@inheritDoc}
     */
    public function getLiveDiagnostics(bool $forceRefresh = false): ?array
    {
        if (! $this->isSipintuConfigured()) {
            return null;
        }

        if ($forceRefresh) {
            Cache::forget('sipintu_live_diagnostics');

            return $this->testConnection();
        }

        $cached = Cache::get('sipintu_live_diagnostics');
        if (
            is_array($cached)
            && is_bool($cached['success'] ?? null)
            && is_bool($cached['status'] ?? null)
            && is_bool($cached['connection'] ?? null)
            && is_string($cached['message'] ?? null)
        ) {
            $result = [
                'success' => $cached['success'],
                'status' => $cached['status'],
                'connection' => $cached['connection'],
                'http_status' => is_int($cached['http_status'] ?? null) ? $cached['http_status'] : null,
                'message' => $cached['message'],
                'error_type' => is_string($cached['error_type'] ?? null) ? $cached['error_type'] : null,
                'detail' => is_string($cached['detail'] ?? null) ? $cached['detail'] : null,
                'troubleshooting' => is_string($cached['troubleshooting'] ?? null) ? $cached['troubleshooting'] : null,
            ];

            if (is_bool($cached['endpoint_warning'] ?? null)) {
                $result['endpoint_warning'] = $cached['endpoint_warning'];
            }
            if (is_array($cached['endpoints'] ?? null)) {
                $result['endpoints'] = $cached['endpoints'];
            }

            return $result;
        }

        return $this->testConnection();
    }

    /**
     * {@inheritDoc}
     *
     * READ-ONLY preview / dry-run. Fetches remote data for the requested scope
     * and classifies each record without any INSERT/UPDATE/DELETE.
     *
     * @param  array<string, mixed>  $options
     * @return array{
     *     success: bool,
     *     scope: string,
     *     included: array{students: bool, teachers: bool, dudis: bool},
     *     message: string,
     *     duration_ms: int,
     *     students: array<string, int|string>,
     *     teachers: array<string, int|string>,
     *     dudis: array<string, int|string>
     * }
     */
    public function preview(string $entityScope = 'all', array $options = []): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $scope = in_array($entityScope, ['all', 'siswa', 'guru', 'dudi'], true) ? $entityScope : 'all';
        $allowCreateDudi = (bool) ($options['allow_create'] ?? $options['create_missing_dudi'] ?? false);

        $runStudents = in_array($scope, ['all', 'siswa'], true);
        $runTeachers = in_array($scope, ['all', 'guru'], true);
        $runDudis = in_array($scope, ['all', 'dudi'], true);

        $start = hrtime(true);

        try {
            $studentPreview = $runStudents ? $this->siPintuService->previewStudents() : $this->emptyPreview();
            $teacherPreview = $runTeachers ? $this->siPintuService->previewTeachers() : $this->emptyPreview();
            $dudiPreview = $runDudis ? $this->siPintuService->previewDudi($allowCreateDudi) : $this->emptyPreview();

            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            $messages = [];
            $existingCounts = Cache::get('sipintu_dashboard_remote_counts', []);
            $newCounts = is_array($existingCounts) ? $existingCounts : [];

            if ($runStudents) {
                $studentTotal = (int) ($studentPreview['total_remote'] ?? 0);
                if (! empty($studentPreview['endpoint_unavailable'])) {
                    $messages[] = (string) ($studentPreview['endpoint_message'] ?? 'Endpoint Siswa belum tersedia (HTTP 404).');
                    $newCounts['students'] = null;
                } else {
                    $messages[] = $studentTotal > 0
                        ? 'Pratinjau data Siswa berhasil ('.number_format($studentTotal).' siswa terdeteksi di SiPintu).'
                        : 'Pratinjau data Siswa selesai (0 data di SiPintu).';
                    $newCounts['students'] = $studentTotal;
                    $newCounts['needs_mapping_students'] = (int) ($studentPreview['perlu_pemetaan'] ?? 0);
                }
            }

            if ($runTeachers) {
                $teacherTotal = (int) ($teacherPreview['total_remote'] ?? 0);
                if (! empty($teacherPreview['endpoint_unavailable'])) {
                    $messages[] = (string) ($teacherPreview['endpoint_message'] ?? 'Endpoint Guru belum tersedia (HTTP 404).');
                    $newCounts['teachers'] = null;
                } else {
                    $messages[] = $teacherTotal > 0
                        ? 'Pratinjau data Guru berhasil ('.number_format($teacherTotal).' guru terdeteksi di SiPintu).'
                        : 'Pratinjau data Guru selesai (0 data di SiPintu).';
                    $newCounts['teachers'] = $teacherTotal;
                }
            }

            if ($runDudis) {
                $dudiTotal = (int) ($dudiPreview['total_remote'] ?? 0);
                if (! empty($dudiPreview['endpoint_unavailable'])) {
                    $messages[] = (string) ($dudiPreview['endpoint_message'] ?? 'Endpoint DUDI belum tersedia (HTTP 404).');
                    $newCounts['dudis'] = null;
                } else {
                    $messages[] = $dudiTotal > 0
                        ? 'Pratinjau data DUDI berhasil ('.number_format($dudiTotal).' DUDI terdeteksi di SiPintu).'
                        : 'Pratinjau data DUDI selesai (0 data di SiPintu).';
                    $newCounts['dudis'] = $dudiTotal;
                }
            }

            $newCounts['updated_at'] = Carbon::now()->toDateTimeString();
            Cache::put('sipintu_dashboard_remote_counts', $newCounts, now()->addHours(6));

            return [
                'success' => true,
                'scope' => $scope,
                'included' => [
                    'students' => $runStudents,
                    'teachers' => $runTeachers,
                    'dudis' => $runDudis,
                ],
                'message' => implode(' ', $messages),
                'duration_ms' => $durationMs,
                'students' => $studentPreview,
                'teachers' => $teacherPreview,
                'dudis' => $dudiPreview,
            ];
        } catch (SiPintuApiException $e) {
            Log::warning('SiPintu preview failed', [
                'exception_class' => $e::class,
                'scope' => $scope,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'scope' => $scope,
                'included' => [
                    'students' => $runStudents,
                    'teachers' => $runTeachers,
                    'dudis' => $runDudis,
                ],
                'message' => $e->getMessage(),
                'duration_ms' => 0,
                'students' => $this->emptyPreview(),
                'teachers' => $this->emptyPreview(),
                'dudis' => $this->emptyPreview(),
            ];
        } catch (\Throwable $e) {
            Log::error('SiPintu preview failed unexpectedly', [
                'exception_class' => $e::class,
                'scope' => $scope,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'scope' => $scope,
                'included' => [
                    'students' => $runStudents,
                    'teachers' => $runTeachers,
                    'dudis' => $runDudis,
                ],
                'message' => 'Preview SiPintu gagal diproses. Data lokal tidak diubah.',
                'duration_ms' => 0,
                'students' => $this->emptyPreview(),
                'teachers' => $this->emptyPreview(),
                'dudis' => $this->emptyPreview(),
            ];
        }
    }

    public function runSync(User $admin, string $entityScope = 'all', array $options = []): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $scope = in_array($entityScope, ['all', 'siswa', 'guru', 'dudi'], true) ? $entityScope : 'all';
        $allowCreateDudi = (bool) ($options['allow_create'] ?? $options['create_missing_dudi'] ?? false);

        $runStudents = in_array($scope, ['all', 'siswa'], true);
        $runTeachers = in_array($scope, ['all', 'guru'], true);
        $runDudis = in_array($scope, ['all', 'dudi'], true);

        $start = hrtime(true);
        $startedAt = Carbon::now();

        Log::info('SiPintu sync started', ['admin_id' => $admin->id, 'entity_scope' => $scope, 'allow_create_dudi' => $allowCreateDudi]);

        try {
            // Network and response validation happen before database writes
            // so a slow/failed API can never hold local locks.
            $payload = $this->siPintuService->fetchSyncPayload($scope);
            $unavailable = $payload['endpoint_unavailable'];

            $studentStats = $runStudents
                ? $this->siPintuService->syncStudents($payload['students'] ?? null, ! empty($unavailable['students']))
                : $this->emptySyncStats();

            if ($runTeachers) {
                if (! empty($unavailable['teachers'])) {
                    $teacherStats = $this->siPintuService->syncTeachers(null, true);
                } elseif (is_array($payload['teachers'] ?? null)) {
                    $teacherStats = $this->siPintuService->syncTeachers($payload['teachers'], false);
                } else {
                    $teacherStats = $this->emptySyncStats();
                }
            } else {
                $teacherStats = $this->emptySyncStats();
            }

            if ($runDudis) {
                if (! empty($unavailable['dudis'])) {
                    $dudiStats = $this->siPintuService->syncDudi(null, $allowCreateDudi, true);
                } elseif (is_array($payload['dudis'] ?? null)) {
                    $dudiStats = $this->siPintuService->syncDudi($payload['dudis'], $allowCreateDudi, false);
                } else {
                    $dudiStats = $this->emptySyncStats();
                }
            } else {
                $dudiStats = $this->emptySyncStats();
            }

            $finishedAt = Carbon::now();
            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);
            $message = $this->buildSummaryMessage($studentStats, $teacherStats, $dudiStats, $scope);

            $singleScopeUnavailable = ($scope === 'siswa' && ! empty($studentStats['endpoint_unavailable']))
                || ($scope === 'guru' && ! empty($teacherStats['endpoint_unavailable']))
                || ($scope === 'dudi' && ! empty($dudiStats['endpoint_unavailable']));

            $hasErrors = ($studentStats['errors'] ?? 0) > 0
                || ($teacherStats['errors'] ?? 0) > 0
                || ($dudiStats['errors'] ?? 0) > 0;
            $unmappedStudents = (int) ($studentStats['needs_mapping'] ?? 0);
            $hasUnmappedStudents = $runStudents && $unmappedStudents > 0;
            $hasUnavailableEndpoints = ($runStudents && ! empty($studentStats['endpoint_unavailable']))
                || ($runTeachers && ! empty($teacherStats['endpoint_unavailable']))
                || ($runDudis && ! empty($dudiStats['endpoint_unavailable']));

            $hasConflicts = ($studentStats['conflicts'] ?? 0) > 0
                || ($teacherStats['conflicts'] ?? 0) > 0
                || ($dudiStats['conflicts'] ?? 0) > 0;

            if ($singleScopeUnavailable) {
                $syncStatus = 'failed';
                $isSuccess = false;
                $isWarning = false;
            } else {
                $isSuccess = true;
                $isWarning = $hasErrors || $hasConflicts || $hasUnmappedStudents || $hasUnavailableEndpoints;
                $syncStatus = $isWarning ? 'partial' : 'success';
            }

            $errorDetails = $this->collectRunIssues($studentStats, $teacherStats, $dudiStats);

            $this->syncLogRepository->create($this->syncLogAttributes($admin, $syncStatus, [
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'entity_scope' => $scope,
                'students' => $studentStats,
                'teachers' => $teacherStats,
                'dudis' => $dudiStats,
                'duration_ms' => $durationMs,
                'message' => $message,
                'error_details' => $errorDetails,
            ]));

            $result = [
                'success' => $isSuccess,
                'status' => $syncStatus,
                'warning' => $isWarning,
                'message' => $message,
                'stats' => [
                    'students' => $studentStats,
                    'teachers' => $teacherStats,
                    'dudis' => $dudiStats,
                ],
            ];

            $existingCounts = Cache::get('sipintu_dashboard_remote_counts', []);
            $newCounts = is_array($existingCounts) ? $existingCounts : [];

            if ($runStudents) {
                $newCounts['students'] = empty($studentStats['endpoint_unavailable'])
                    ? (int) ($studentStats['received'] ?? count($payload['students'] ?? []))
                    : null;
                $newCounts['needs_mapping_students'] = $unmappedStudents;
            }
            if ($runTeachers) {
                $newCounts['teachers'] = empty($teacherStats['endpoint_unavailable']) && is_array($payload['teachers'] ?? null)
                    ? (int) ($teacherStats['received'] ?? count($payload['teachers']))
                    : null;
            }
            if ($runDudis) {
                $newCounts['dudis'] = empty($dudiStats['endpoint_unavailable']) && is_array($payload['dudis'] ?? null)
                    ? (int) ($dudiStats['received'] ?? count($payload['dudis']))
                    : null;
            }
            $newCounts['updated_at'] = $finishedAt->toDateTimeString();

            Cache::put('sipintu_dashboard_remote_counts', $newCounts, now()->addHours(6));

            if ($hasUnavailableEndpoints) {
                Cache::forget('sipintu_live_diagnostics');
            }

            Log::info('SiPintu sync completed', [
                'admin_id' => $admin->id,
                'entity_scope' => $scope,
                'status' => $syncStatus,
                'student_stats' => $result['stats']['students'],
                'teacher_stats' => $result['stats']['teachers'],
                'dudi_stats' => $result['stats']['dudis'],
            ]);

            return $result;
        } catch (SiPintuApiException $e) {
            return $this->failedSyncResult($admin, $start, $startedAt, $scope, $e->getMessage(), $e);
        } catch (QueryException $e) {
            return $this->failedSyncResult(
                $admin,
                $start,
                $startedAt,
                $scope,
                'Sinkronisasi gagal disimpan. Semua perubahan data dibatalkan.',
                $e,
            );
        } catch (\Throwable $e) {
            return $this->failedSyncResult(
                $admin,
                $start,
                $startedAt,
                $scope,
                'Sinkronisasi gagal diproses. Semua perubahan data dibatalkan.',
                $e,
            );
        }
    }

    public function testConnection(): array
    {
        $result = $this->siPintuService->testConnection();

        if ($result['success']) {
            try {
                $statuses = $this->siPintuService->checkEndpointsStatus([
                    SiPintuRepositoryInterface::ENDPOINT_STUDENTS,
                    SiPintuRepositoryInterface::ENDPOINT_TEACHERS,
                    SiPintuRepositoryInterface::ENDPOINT_DUDI,
                ]);

                $fallback = static fn (string $ep): array => [
                    'status' => null,
                    'http_status' => null,
                    'success' => false,
                    'error_type' => 'connection',
                    'message' => "Gagal memeriksa endpoint {$ep}.",
                    'count' => null,
                ];

                $studentStatus = $statuses[SiPintuRepositoryInterface::ENDPOINT_STUDENTS] ?? $fallback(SiPintuRepositoryInterface::ENDPOINT_STUDENTS);
                $teacherStatus = $statuses[SiPintuRepositoryInterface::ENDPOINT_TEACHERS] ?? $fallback(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);
                $dudiStatus = $statuses[SiPintuRepositoryInterface::ENDPOINT_DUDI] ?? $fallback(SiPintuRepositoryInterface::ENDPOINT_DUDI);

                $studentHttp = $studentStatus['http_status'] ?? $studentStatus['status'];
                $teacherHttp = $teacherStatus['http_status'] ?? $teacherStatus['status'];
                $dudiHttp = $dudiStatus['http_status'] ?? $dudiStatus['status'];

                $result['endpoints'] = [
                    'gateway' => [
                        'name' => 'Gateway SiPintu ('.SiPintuRepositoryInterface::ENDPOINT_HEALTH.')',
                        'status' => $result['http_status'] === 200,
                        'http_status' => $result['http_status'],
                        'error_type' => $result['error_type'],
                        'message' => $result['message'],
                    ],
                    'profile' => [
                        'name' => 'Endpoint Profil Pengguna ('.SiPintuRepositoryInterface::ENDPOINT_USER.')',
                        'status' => true,
                        'http_status' => null,
                        'error_type' => null,
                        'message' => 'Endpoint OAuth SSO aktif untuk autentikasi login pengguna.',
                    ],
                    'students' => [
                        'name' => 'Endpoint Data Siswa ('.SiPintuRepositoryInterface::ENDPOINT_STUDENTS.')',
                        'status' => $studentStatus['success'],
                        'http_status' => $studentHttp,
                        'error_type' => $studentStatus['error_type'] ?? null,
                        'message' => $studentStatus['message'],
                        'count' => $studentStatus['count'] ?? null,
                    ],
                    'teachers' => [
                        'name' => 'Endpoint Data Guru ('.SiPintuRepositoryInterface::ENDPOINT_TEACHERS.')',
                        'status' => $teacherStatus['success'],
                        'http_status' => $teacherHttp,
                        'error_type' => $teacherStatus['error_type'] ?? null,
                        'message' => $teacherStatus['message'],
                        'count' => $teacherStatus['count'] ?? null,
                    ],
                    'dudi' => [
                        'name' => 'Endpoint Data DUDI ('.SiPintuRepositoryInterface::ENDPOINT_DUDI.')',
                        'status' => $dudiStatus['success'],
                        'http_status' => $dudiHttp,
                        'error_type' => $dudiStatus['error_type'] ?? null,
                        'message' => $dudiStatus['message'],
                        'count' => $dudiStatus['count'] ?? null,
                    ],
                ];

                $existingCounts = Cache::get('sipintu_dashboard_remote_counts', []);
                $existingCounts = is_array($existingCounts) ? $existingCounts : [];

                Cache::put('sipintu_dashboard_remote_counts', array_merge($existingCounts, [
                    'students' => $studentStatus['success'] ? $studentStatus['count'] : null,
                    'teachers' => $teacherStatus['success'] ? $teacherStatus['count'] : null,
                    'dudis' => $dudiStatus['success'] ? $dudiStatus['count'] : null,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]), now()->addHours(6));

                $missing = [];
                if (! $studentStatus['success']) {
                    $missing[] = $this->formatEndpointIssueTag('Siswa', $studentStatus, $studentHttp);
                }
                if (! $teacherStatus['success']) {
                    $missing[] = $this->formatEndpointIssueTag('Guru', $teacherStatus, $teacherHttp);
                }
                if (! $dudiStatus['success']) {
                    $missing[] = $this->formatEndpointIssueTag('DUDI', $dudiStatus, $dudiHttp);
                }

                if ($missing !== []) {
                    $result['endpoint_warning'] = true;
                    $activeList = [];
                    if ($studentStatus['success']) {
                        $activeList[] = 'Siswa (HTTP '.$studentHttp.')';
                    }
                    if ($teacherStatus['success']) {
                        $activeList[] = 'Guru (HTTP '.$teacherHttp.')';
                    }
                    if ($dudiStatus['success']) {
                        $activeList[] = 'DUDI (HTTP '.$dudiHttp.')';
                    }

                    $activeSummary = $activeList !== [] ? 'Layanan aktif: '.implode(', ', $activeList).'.' : 'Tidak ada endpoint data master yang merespons aktif.';
                    $result['message'] = 'Koneksi ke Gateway SiPintu berhasil (Sebagian Tersedia). '.$activeSummary.' Bermasalah / belum tersedia: '.implode(', ', $missing).'.';
                    $result['detail'] = $activeSummary.' Rincian endpoint bermasalah: '.implode(', ', $missing).'.';

                    $hasTimeout = in_array('timeout', [
                        $studentStatus['error_type'] ?? null,
                        $teacherStatus['error_type'] ?? null,
                        $dudiStatus['error_type'] ?? null,
                    ], true);
                    $hasNotFound = in_array(404, [$studentHttp, $teacherHttp, $dudiHttp], true);

                    $hints = ['Sinkronisasi berjalan aman untuk endpoint berstatus aktif (HTTP 200 dengan JSON valid).'];
                    if ($hasTimeout) {
                        $hints[] = 'Untuk endpoint yang melebihi batas waktu (Timeout), coba ulangi Test Connection beberapa saat lagi atau periksa beban server SiPintu.';
                    }
                    if ($hasNotFound) {
                        $hints[] = 'Untuk endpoint berstatus belum tersedia, pengelola server SiPintu perlu menyiapkan route terkait.';
                    }
                    $result['troubleshooting'] = implode(' ', $hints);
                }
            } catch (\Throwable) {
                // Retain base connection result if secondary probes fail
            }
        }

        Cache::put('sipintu_live_diagnostics', $result, now()->addMinutes(5));

        return $result;
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function formatEndpointIssueTag(string $label, array $status, ?int $httpStatus): string
    {
        $errorType = $status['error_type'] ?? null;

        if ($errorType === 'timeout') {
            return "{$label} (Timeout)";
        }

        if ($httpStatus !== null) {
            return "{$label} (HTTP {$httpStatus})";
        }

        return "{$label} (Error Koneksi)";
    }

    /**
     * Collect structured failure/warning details for audit trail inspection.
     *
     * @param  array<string, int>  $studentStats
     * @param  array<string, int>  $teacherStats
     * @param  array<string, int>  $dudiStats
     * @return list<array<string, mixed>>|null
     */
    private function collectRunIssues(array $studentStats, array $teacherStats, array $dudiStats): ?array
    {
        $issues = [];

        if (! empty($studentStats['endpoint_unavailable'])) {
            $issues[] = ['entity' => 'students', 'type' => 'endpoint_unavailable', 'http_status' => 404, 'detail' => 'Endpoint Siswa ('.SiPintuRepositoryInterface::ENDPOINT_STUDENTS.') belum tersedia (HTTP 404).'];
        }
        if (($studentStats['needs_mapping'] ?? 0) > 0) {
            $issues[] = ['entity' => 'students', 'type' => 'needs_mapping', 'count' => (int) $studentStats['needs_mapping'], 'detail' => 'Siswa dilewati karena classroom_id belum memiliki pemetaan kelas lokal yang valid.'];
        }
        if (($studentStats['conflicts'] ?? 0) > 0) {
            $issues[] = ['entity' => 'students', 'type' => 'conflict', 'count' => (int) $studentStats['conflicts'], 'detail' => 'Benturan NIS/NISN, email terpakai akun lain, atau akun terlindungi (Super Admin/DUDI).'];
        }
        if (($studentStats['duplicates'] ?? 0) > 0) {
            $issues[] = ['entity' => 'students', 'type' => 'duplicate', 'count' => (int) $studentStats['duplicates'], 'detail' => 'NIS duplikat di dalam payload API SiPintu dilewati.'];
        }
        if (($studentStats['errors'] ?? 0) > 0) {
            $issues[] = ['entity' => 'students', 'type' => 'error', 'count' => (int) $studentStats['errors'], 'detail' => 'Payload siswa tidak valid atau gagal disimpan dalam transaksi database.'];
        }
        if (! empty($teacherStats['endpoint_unavailable'])) {
            $issues[] = ['entity' => 'teachers', 'type' => 'endpoint_unavailable', 'http_status' => 404, 'detail' => 'Endpoint Guru ('.SiPintuRepositoryInterface::ENDPOINT_TEACHERS.') belum tersedia (HTTP 404).'];
        }
        if (($teacherStats['conflicts'] ?? 0) > 0) {
            $issues[] = ['entity' => 'teachers', 'type' => 'conflict', 'count' => (int) $teacherStats['conflicts'], 'detail' => 'Email guru bentrok dengan akun lain/terlindungi (Super Admin/DUDI/Siswa) atau NIP berbeda.'];
        }
        if (($teacherStats['duplicates'] ?? 0) > 0) {
            $issues[] = ['entity' => 'teachers', 'type' => 'duplicate', 'count' => (int) $teacherStats['duplicates'], 'detail' => 'NIP duplikat di dalam payload API SiPintu dilewati.'];
        }
        if (($teacherStats['errors'] ?? 0) > 0) {
            $issues[] = ['entity' => 'teachers', 'type' => 'error', 'count' => (int) $teacherStats['errors'], 'detail' => 'Payload guru tidak valid atau gagal disimpan dalam transaksi database.'];
        }
        $teacherSoftSkipped = max(
            0,
            (int) ($teacherStats['skipped'] ?? 0)
                - (int) ($teacherStats['conflicts'] ?? 0)
                - (int) ($teacherStats['duplicates'] ?? 0)
                - (int) ($teacherStats['errors'] ?? 0)
        );
        if ($teacherSoftSkipped > 0) {
            $issues[] = ['entity' => 'teachers', 'type' => 'soft_deleted_preserved', 'count' => $teacherSoftSkipped, 'detail' => 'Data guru berstatus soft-deleted dilewati agar tidak membuat duplikat atau auto-restore tanpa tindakan Admin.'];
        }
        if (! empty($dudiStats['endpoint_unavailable'])) {
            $issues[] = ['entity' => 'dudis', 'type' => 'endpoint_unavailable', 'http_status' => 404, 'detail' => 'Endpoint Master DUDI ('.SiPintuRepositoryInterface::ENDPOINT_DUDI.') belum tersedia (HTTP 404).'];
        }
        if (($dudiStats['conflicts'] ?? 0) > 0) {
            $issues[] = ['entity' => 'dudis', 'type' => 'conflict', 'count' => (int) $dudiStats['conflicts'], 'detail' => 'Benturan email/identitas DUDI dengan akun non-DUDI atau Super Admin.'];
        }
        if (($dudiStats['duplicates'] ?? 0) > 0) {
            $issues[] = ['entity' => 'dudis', 'type' => 'duplicate', 'count' => (int) $dudiStats['duplicates'], 'detail' => 'ID DUDI duplikat di dalam payload API SiPintu dilewati.'];
        }
        if (($dudiStats['errors'] ?? 0) > 0) {
            $issues[] = ['entity' => 'dudis', 'type' => 'error', 'count' => (int) $dudiStats['errors'], 'detail' => 'Payload DUDI tidak valid atau gagal disimpan dalam transaksi database.'];
        }

        return $issues === [] ? null : $issues;
    }

    /**
     * @return array{
     *     success: false,
     *     status: string,
     *     warning: bool,
     *     message: string,
     *     stats: array{
     *         students: array<string, int>,
     *         teachers: array<string, int>,
     *         dudis?: array<string, int>
     *     }
     * }
     */
    private function failedSyncResult(
        User $admin,
        int $start,
        Carbon $startedAt,
        string $entityScope,
        string $message,
        \Throwable $exception,
    ): array {
        $finishedAt = Carbon::now();
        $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

        Log::error('SiPintu sync failed', [
            'admin_id' => $admin->id,
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
        ]);

        try {
            $this->syncLogRepository->create($this->syncLogAttributes($admin, 'failed', [
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'entity_scope' => $entityScope,
                'students' => $this->emptySyncStats(),
                'teachers' => $this->emptySyncStats(),
                'dudis' => $this->emptySyncStats(),
                'duration_ms' => $durationMs,
                'message' => $message,
                'error_details' => [[
                    'entity' => $entityScope,
                    'type' => 'exception',
                    'exception_class' => $exception::class,
                    'detail' => $exception->getMessage(),
                ]],
            ]));
        } catch (QueryException $logException) {
            Log::error('SiPintu failed sync history could not be recorded', [
                'admin_id' => $admin->id,
                'exception_class' => $logException::class,
                'message' => $logException->getMessage(),
            ]);
        }

        return [
            'success' => false,
            'status' => 'failed',
            'warning' => false,
            'message' => $message,
            'stats' => [
                'students' => $this->emptySyncStats(),
                'teachers' => $this->emptySyncStats(),
                'dudis' => $this->emptySyncStats(),
            ],
        ];
    }

    /**
     * @param  'success'|'failed'|'partial'  $status
     * @param  array{
     *     started_at?: Carbon,
     *     finished_at?: Carbon,
     *     entity_scope?: string,
     *     students: array<string, int>,
     *     teachers: array<string, int>,
     *     dudis?: array<string, int>,
     *     duration_ms: int,
     *     message: string,
     *     error_details?: list<array<string, mixed>>|null
     * }  $context
     * @return array<string, mixed>
     */
    private function syncLogAttributes(User $admin, string $status, array $context): array
    {
        $studentStats = $context['students'];
        $teacherStats = $context['teachers'];
        $dudiStats = $context['dudis'] ?? [];

        $studentReceived = (int) ($studentStats['received'] ?? (
            ($studentStats['created'] ?? 0)
            + ($studentStats['updated'] ?? 0)
            + ($studentStats['unchanged'] ?? 0)
            + ($studentStats['skipped'] ?? 0)
        ));

        $remoteTotal = $studentReceived
            + (int) ($teacherStats['received'] ?? 0)
            + (int) ($dudiStats['received'] ?? 0);

        return [
            'user_id' => $admin->id,
            'admin_name' => $admin->name,
            'started_at' => $context['started_at'] ?? null,
            'finished_at' => $context['finished_at'] ?? null,
            'status' => $status,
            'entity_scope' => $context['entity_scope'] ?? 'all',
            'remote_total' => $remoteTotal,
            'added' => (int) ($studentStats['created'] ?? 0),
            'updated' => (int) ($studentStats['updated'] ?? 0),
            'deleted' => (int) ($studentStats['deleted'] ?? 0),
            'skipped' => (int) ($studentStats['skipped'] ?? 0),
            'unchanged' => (int) ($studentStats['unchanged'] ?? 0) + (int) ($teacherStats['unchanged'] ?? 0) + (int) ($dudiStats['unchanged'] ?? 0),
            'needs_mapping' => (int) ($studentStats['needs_mapping'] ?? 0),
            'conflicts' => (int) ($studentStats['conflicts'] ?? 0) + (int) ($teacherStats['conflicts'] ?? 0) + (int) ($dudiStats['conflicts'] ?? 0),
            'duplicates' => (int) ($studentStats['duplicates'] ?? 0) + (int) ($teacherStats['duplicates'] ?? 0) + (int) ($dudiStats['duplicates'] ?? 0),
            'invalid' => (int) ($studentStats['invalid'] ?? 0) + (int) ($teacherStats['invalid'] ?? 0) + (int) ($dudiStats['invalid'] ?? 0),
            'failed' => (int) ($studentStats['failed'] ?? 0) + (int) ($teacherStats['failed'] ?? 0) + (int) ($dudiStats['failed'] ?? 0),
            'teacher_added' => (int) ($teacherStats['created'] ?? 0),
            'teacher_updated' => (int) ($teacherStats['updated'] ?? 0),
            'teacher_deleted' => (int) ($teacherStats['deleted'] ?? 0),
            'teacher_skipped' => (int) ($teacherStats['skipped'] ?? 0),
            'dudi_added' => (int) ($dudiStats['created'] ?? 0),
            'dudi_updated' => (int) ($dudiStats['updated'] ?? 0),
            'dudi_deleted' => (int) ($dudiStats['deleted'] ?? 0),
            'dudi_skipped' => (int) ($dudiStats['skipped'] ?? 0),
            'duration_ms' => $context['duration_ms'],
            'summary' => [
                'students' => $studentStats,
                'teachers' => $teacherStats,
                'dudis' => $dudiStats,
            ],
            'error_details' => $context['error_details'] ?? null,
            'message' => $context['message'],
        ];
    }

    /**
     * Build a human-readable summary from sync stats scoped to requested entities.
     *
     * @param  array<string, int>  $studentStats
     * @param  array<string, int>  $teacherStats
     * @param  array<string, int>  $dudiStats
     */
    private function buildSummaryMessage(array $studentStats, array $teacherStats, array $dudiStats = [], string $entityScope = 'all'): string
    {
        $scope = in_array($entityScope, ['all', 'siswa', 'guru', 'dudi'], true) ? $entityScope : 'all';
        $runStudents = in_array($scope, ['all', 'siswa'], true);
        $runTeachers = in_array($scope, ['all', 'guru'], true);
        $runDudis = in_array($scope, ['all', 'dudi'], true);

        $students = $this->summaryStats($studentStats);
        $teachers = $this->summaryStats($teacherStats);
        $dudis = $this->summaryStats($dudiStats);

        $parts = [];
        $processed = ($runStudents ? array_sum($students) : 0)
            + ($runTeachers ? array_sum($teachers) : 0)
            + ($runDudis ? array_sum($dudis) : 0);

        if ($runStudents) {
            if (! empty($studentStats['endpoint_unavailable'])) {
                $parts[] = 'Endpoint API Siswa SiPintu ('.SiPintuRepositoryInterface::ENDPOINT_STUDENTS.') belum tersedia (HTTP 404). Data siswa lokal tidak diubah.';
            } else {
                $studentExtra = [];
                if ($students['duplicates'] > 0) {
                    $studentExtra[] = $students['duplicates'].' duplikat';
                }
                if ($students['invalid'] > 0) {
                    $studentExtra[] = $students['invalid'].' tidak valid';
                }
                if ($students['failed'] > 0) {
                    $studentExtra[] = $students['failed'].' gagal';
                }

                $studentMsg = 'Siswa: '.$students['created'].' baru, '.$students['updated'].' diperbarui, '
                    .$students['unchanged'].' tidak berubah, '.$students['skipped'].' dilewati '
                    .'('.$students['needs_mapping'].' perlu pemetaan, '.$students['conflicts'].' konflik), '
                    .$students['errors'].' error.'
                    .($studentExtra !== [] ? ' Rincian lain: '.implode(', ', $studentExtra).'.' : '');

                if ($students['needs_mapping'] > 0 && ($students['created'] + $students['updated'] + $students['unchanged']) === 0) {
                    $studentMsg .= ' Perhatian: Siswa SiPintu belum tersimpan ke database SIMONGAN karena memerlukan pemetaan kelas ('.$students['needs_mapping'].' siswa perlu pemetaan). Silakan lakukan pemetaan di menu Pemetaan Kelas SiPintu lalu sinkronkan ulang.';
                }

                $parts[] = $studentMsg;
            }
        }

        if ($runTeachers) {
            if (! empty($teacherStats['endpoint_unavailable'])) {
                $parts[] = $scope === 'guru'
                    ? 'Endpoint API Guru SiPintu ('.SiPintuRepositoryInterface::ENDPOINT_TEACHERS.') belum tersedia (HTTP 404). Data guru lokal tidak diubah.'
                    : 'Guru: Endpoint '.SiPintuRepositoryInterface::ENDPOINT_TEACHERS.' belum tersedia pada server SiPintu (HTTP 404). Data guru lokal aman.';
            } elseif (array_sum($teachers) === 0) {
                $parts[] = 'Guru: 0 diproses.';
            } else {
                $parts[] = 'Guru: '.$teachers['created'].' baru, '
                    .$teachers['updated'].' diperbarui, '.$teachers['unchanged'].' tidak berubah, '
                    .$teachers['skipped'].' dilewati ('.$teachers['conflicts'].' konflik), '
                    .$teachers['errors'].' error.';
            }
        }

        if ($runDudis) {
            if (! empty($dudiStats['endpoint_unavailable'])) {
                $parts[] = $scope === 'dudi'
                    ? 'Endpoint API Master DUDI SiPintu ('.SiPintuRepositoryInterface::ENDPOINT_DUDI.') belum tersedia (HTTP 404). Data DUDI lokal tidak diubah.'
                    : 'DUDI: Endpoint '.SiPintuRepositoryInterface::ENDPOINT_DUDI.' belum tersedia pada server SiPintu (HTTP 404). Data DUDI lokal aman.';
            } elseif (array_sum($dudis) === 0) {
                $parts[] = 'DUDI: 0 diproses.';
            } else {
                $parts[] = 'DUDI: '.$dudis['created'].' baru, '
                    .$dudis['updated'].' diperbarui, '.$dudis['unchanged'].' tidak berubah, '
                    .$dudis['skipped'].' dilewati ('.$dudis['conflicts'].' konflik), '
                    .$dudis['errors'].' error.';
            }
        }

        $anyUnavailable = ($runStudents && ! empty($studentStats['endpoint_unavailable']))
            || ($runTeachers && ! empty($teacherStats['endpoint_unavailable']))
            || ($runDudis && ! empty($dudiStats['endpoint_unavailable']));

        if ($processed === 0 && ! $anyUnavailable) {
            return 'Sinkronisasi selesai. Tidak ada data baru yang ditemukan. Ditemukan: 0, ditambahkan: 0, diperbarui: 0, dilewati: 0, gagal: 0.';
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, int>  $stats
     * @return array{created: int, updated: int, skipped: int, unchanged: int, duplicates: int, conflicts: int, needs_mapping: int, invalid: int, failed: int, errors: int}
     */
    private function summaryStats(array $stats): array
    {
        return [
            'created' => (int) ($stats['created'] ?? 0),
            'updated' => (int) ($stats['updated'] ?? 0),
            'skipped' => (int) ($stats['skipped'] ?? 0),
            'unchanged' => (int) ($stats['unchanged'] ?? 0),
            'duplicates' => (int) ($stats['duplicates'] ?? 0),
            'conflicts' => (int) ($stats['conflicts'] ?? 0),
            'needs_mapping' => (int) ($stats['needs_mapping'] ?? 0),
            'invalid' => (int) ($stats['invalid'] ?? 0),
            'failed' => (int) ($stats['failed'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLog(SiPintuSyncLog $log): array
    {
        return [
            'id' => $log->id,
            'admin_name' => $log->admin_name,
            'started_at' => $log->started_at?->toDateTimeString(),
            'finished_at' => $log->finished_at?->toDateTimeString(),
            'status' => $log->status,
            'entity_scope' => $log->entity_scope,
            'remote_total' => $log->remote_total,
            'added' => $log->added,
            'updated' => $log->updated,
            'deleted' => $log->deleted,
            'skipped' => $log->skipped,
            'unchanged' => $log->unchanged,
            'needs_mapping' => $log->needs_mapping,
            'conflicts' => $log->conflicts,
            'duplicates' => $log->duplicates,
            'invalid' => $log->invalid,
            'failed' => $log->failed,
            'teacher_added' => $log->teacher_added,
            'teacher_updated' => $log->teacher_updated,
            'teacher_deleted' => $log->teacher_deleted,
            'teacher_skipped' => $log->teacher_skipped,
            'dudi_added' => $log->dudi_added,
            'dudi_updated' => $log->dudi_updated,
            'dudi_deleted' => $log->dudi_deleted,
            'dudi_skipped' => $log->dudi_skipped,
            'duration_ms' => $log->duration_ms,
            'summary' => $log->summary,
            'error_details' => $log->error_details,
            'message' => $log->message,
            'ran_at' => $log->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyPreview(): array
    {
        return [
            'baru' => 0,
            'diperbarui' => 0,
            'tidak_berubah' => 0,
            'perlu_pemetaan' => 0,
            'dilewati' => 0,
            'duplikat' => 0,
            'konflik' => 0,
            'tidak_valid' => 0,
            'gagal' => 0,
            'tidak_ditemukan' => 0,
            'error' => 0,
            'total_remote' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptySyncStats(): array
    {
        return [
            'received' => 0,
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'unchanged' => 0,
            'duplicates' => 0,
            'conflicts' => 0,
            'needs_mapping' => 0,
            'invalid' => 0,
            'failed' => 0,
            'errors' => 0,
        ];
    }
}

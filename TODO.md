# TODO — Sinkronisasi SiPintu (Super Admin Feature)

- [x] Analyze existing SiPintu backend (reuse: Repository, Service, Exception, Config, Command)
- [ ] Create migration: `sipintu_sync_logs` table
- [ ] Create `App\Models\SiPintuSyncLog`
- [ ] Create `SipintuSyncLogRepositoryInterface` + `SipintuSyncLogRepository`
- [ ] Create `SipintuSyncServiceInterface` + `SipintuSyncService`
- [ ] Create `Admin\SipintuSyncController`
- [ ] Create view: `resources/views/admin/sipintu-sync/index.blade.php`
- [ ] Add routes (GET/POST `admin/sipintu-sync`)
- [ ] Register repository + service in providers
- [ ] Add sidebar menu (layouts/navigation.blade.php)
- [ ] Run migration, route:list, build, clear cache
- [ ] Final report

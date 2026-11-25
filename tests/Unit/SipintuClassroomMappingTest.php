<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Services\Support\SipintuClassroomMatcher;
use PHPUnit\Framework\TestCase;

class SipintuClassroomMappingTest extends TestCase
{
    private function makeKelas(int $id, string $nama, int $tingkat, string $kodeJurusan, string $namaJurusan = ''): Kelas
    {
        $jurusan = new Jurusan([
            'kode' => $kodeJurusan,
            'nama' => $namaJurusan !== '' ? $namaJurusan : "Kompetensi {$kodeJurusan}",
        ]);
        $jurusan->id = $id + 100;

        $kelas = new Kelas([
            'nama' => $nama,
            'tingkat' => $tingkat,
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran' => '2025/2026',
        ]);
        $kelas->id = $id;
        $kelas->setRelation('jurusan', $jurusan);

        return $kelas;
    }

    public function test_extracts_classroom_id_consistently_for_int_string_nested_and_alumni(): void
    {
        $this->assertSame(6, SipintuClassroomMatcher::extractClassroomId(['classroom_id' => 6]));
        $this->assertSame(48, SipintuClassroomMatcher::extractClassroomId(['classroom_id' => '48']));
        $this->assertSame(50, SipintuClassroomMatcher::extractClassroomId(['class_id' => '50']));
        $this->assertSame(12, SipintuClassroomMatcher::extractClassroomId(['classroom' => ['id' => 12, 'name' => 'XII PPLG 1']]));

        // Alumni without explicit classroom_id must return 0 even if nested classroom object exists
        $this->assertSame(0, SipintuClassroomMatcher::extractClassroomId([
            'graduated' => true,
            'classroom_id' => null,
            'classroom' => ['id' => 9],
        ]));
        $this->assertSame(0, SipintuClassroomMatcher::extractClassroomId(['status' => 'alumni']));
    }

    public function test_extracts_classroom_name_only_when_present_without_fabricating(): void
    {
        $this->assertSame('X PM 2', SipintuClassroomMatcher::extractClassroomName(['classroom' => ['id' => 6, 'name' => ' X PM 2 ']]));
        $this->assertSame('XI AKL 1', SipintuClassroomMatcher::extractClassroomName(['classroom_name' => 'XI AKL 1']));
        $this->assertSame('XII TO 2', SipintuClassroomMatcher::extractClassroomName(['kelas' => 'XII TO 2']));
        $this->assertNull(SipintuClassroomMatcher::extractClassroomName(['classroom_id' => 6, 'classroom' => ['id' => 6]]));
    }

    public function test_parses_tingkat_and_jurusan_from_classroom_name_and_explicit_fields(): void
    {
        $remote = ['classroom_id' => 50, 'classroom' => ['id' => 50, 'name' => 'XII PPLG 1']];
        $this->assertSame(12, SipintuClassroomMatcher::extractRemoteTingkat($remote));
        $this->assertSame('PPLG', SipintuClassroomMatcher::extractRemoteJurusan($remote));

        $explicitRemote = [
            'classroom_id' => 9,
            'classroom' => ['id' => 9, 'name' => 'X MPLB 3', 'tingkat' => 10, 'jurusan' => 'MPLB'],
        ];
        $this->assertSame(10, SipintuClassroomMatcher::extractRemoteTingkat($explicitRemote));
        $this->assertSame('MPLB', SipintuClassroomMatcher::extractRemoteJurusan($explicitRemote));
    }

    public function test_strict_match_succeeds_only_when_name_tingkat_jurusan_and_cardinality_match(): void
    {
        $kelasValid = $this->makeKelas(30, 'XII PPLG 1', 12, 'PPLG');
        $kelasOther = $this->makeKelas(7, 'X PM 2', 10, 'PM');

        $remote = ['classroom_id' => 50, 'classroom' => ['id' => 50, 'name' => 'XII PPLG 1']];
        $result = SipintuClassroomMatcher::findUniqueStrictMatch($remote, [$kelasOther, $kelasValid]);

        $this->assertNotNull($result['kelas']);
        $this->assertSame(30, $result['kelas']->id);
        $this->assertSame('exact_match', $result['reason']);
    }

    public function test_strict_match_rejects_mismatched_tingkat_or_jurusan(): void
    {
        // Same name 'XII PPLG 1' but wrong tingkat (11)
        $wrongTingkat = $this->makeKelas(30, 'XII PPLG 1', 11, 'PPLG');
        $remote = ['classroom_id' => 50, 'classroom' => ['id' => 50, 'name' => 'XII PPLG 1']];

        $res1 = SipintuClassroomMatcher::findUniqueStrictMatch($remote, [$wrongTingkat]);
        $this->assertNull($res1['kelas']);
        $this->assertSame('tingkat_mismatch', $res1['reason']);

        // Same name and tingkat (12) but wrong jurusan ('AKL')
        $wrongJurusan = $this->makeKelas(31, 'XII PPLG 1', 12, 'AKL');
        $res2 = SipintuClassroomMatcher::findUniqueStrictMatch($remote, [$wrongJurusan]);
        $this->assertNull($res2['kelas']);
        $this->assertSame('jurusan_mismatch', $res2['reason']);
    }

    public function test_strict_match_rejects_ambiguous_multiple_local_classes_or_already_mapped_conflict(): void
    {
        $k1 = $this->makeKelas(1, 'XII PPLG 1', 12, 'PPLG');
        $k2 = $this->makeKelas(2, 'XII PPLG 1', 12, 'PPLG');
        $remote = ['classroom_id' => 50, 'classroom' => ['id' => 50, 'name' => 'XII PPLG 1']];

        $ambiguous = SipintuClassroomMatcher::findUniqueStrictMatch($remote, [$k1, $k2]);
        $this->assertNull($ambiguous['kelas']);
        $this->assertSame('ambiguous_multiple_matches', $ambiguous['reason']);

        // Conflict when local kelas ID 1 is already mapped to another classroom_id (99)
        $conflict = SipintuClassroomMatcher::findUniqueStrictMatch($remote, [$k1], [1 => 99]);
        $this->assertNull($conflict['kelas']);
        $this->assertSame('kelas_already_mapped_to_another_classroom', $conflict['reason']);
    }
}

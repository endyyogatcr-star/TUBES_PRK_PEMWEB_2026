<?php
require __DIR__ . '/bootstrap.php';

$idPenugasan = positiveId($_GET['id_penugasan'] ?? 0);

// Validasi penugasan dan hak akses
$stmt = $pdo->prepare('SELECT * FROM PENUGASAN_MENGAJAR WHERE id_penugasan = ?');
$stmt->execute([$idPenugasan]);
$penugasan = $stmt->fetch();
if (!$penugasan) respond(null, 'Penugasan tidak ditemukan.', 404);
if (!isAdmin() && (int)$penugasan['id_guru'] !== (int)$_SESSION['guru']['id_guru']) {
    respond(null, 'Anda tidak memiliki akses ke penugasan ini.', 403);
}

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT r.*, s.nisn, s.nama_siswa
        FROM REKAP_AKADEMIK_KUADRAN r
        JOIN SISWA s ON s.id_siswa = r.id_siswa
        WHERE r.id_penugasan = ?
        ORDER BY s.nama_siswa');
    $stmt->execute([$idPenugasan]);
    respond($stmt->fetchAll());
}

if ($method !== 'POST') respond(null, 'Metode tidak didukung.', 405);

// Hitung rekap otomatis

$stmt = $pdo->prepare('SELECT * FROM PENGATURAN_BOBOT WHERE id_penugasan = ?');
$stmt->execute([$idPenugasan]);
$bobot = $stmt->fetch();
if (!$bobot) {
    $bobot = ['bobot_tugas' => 25, 'bobot_kuis' => 25, 'bobot_uts' => 25, 'bobot_uas' => 25];
}

$stmt = $pdo->prepare('SELECT * FROM PENILAIAN WHERE id_penugasan = ?');
$stmt->execute([$idPenugasan]);
$daftarPenilaian = $stmt->fetchAll();

$penilaianPerJenis = ['Tugas' => [], 'Kuis' => [], 'UTS' => [], 'UAS' => []];
foreach ($daftarPenilaian as $p) {
    $penilaianPerJenis[$p['jenis_komponen']][] = $p['id_penilaian'];
}

$stmt = $pdo->prepare('SELECT id_siswa FROM SISWA WHERE id_kelas = ?');
$stmt->execute([$penugasan['id_kelas']]);
$daftarSiswa = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($daftarSiswa)) respond(null, 'Tidak ada siswa di kelas ini.', 422);

$idPenilaianAll = array_column($daftarPenilaian, 'id_penilaian');
$nilaiMap = [];
if (!empty($idPenilaianAll)) {
    $marks = implode(',', array_fill(0, count($idPenilaianAll), '?'));
    $stmt = $pdo->prepare("SELECT * FROM NILAI_DAN_PENGUMPULAN WHERE id_penilaian IN ($marks)");
    $stmt->execute($idPenilaianAll);
    foreach ($stmt->fetchAll() as $n) {
        $nilaiMap[$n['id_siswa']][$n['id_penilaian']] = $n;
    }
}

$pdo->beginTransaction();
$hasilRekap = [];
$totalPenilaianAda = count($daftarPenilaian);

foreach ($daftarSiswa as $idSiswa) {
    $nilaiSiswa = $nilaiMap[$idSiswa] ?? [];

    // Rata-rata per jenis komponen
    $rataPerJenis = [];
    foreach ($penilaianPerJenis as $jenis => $ids) {
        $nilaiJenis = [];
        foreach ($ids as $idP) {
            if (isset($nilaiSiswa[$idP]) && $nilaiSiswa[$idP]['nilai'] !== null) {
                $nilaiJenis[] = (float)$nilaiSiswa[$idP]['nilai'];
            }
        }
        $rataPerJenis[$jenis] = !empty($nilaiJenis) ? array_sum($nilaiJenis) / count($nilaiJenis) : 0;
    }

    // Nilai akhir berbobot
    $nilaiAkhir = ($rataPerJenis['Tugas'] * $bobot['bobot_tugas']
                 + $rataPerJenis['Kuis']  * $bobot['bobot_kuis']
                 + $rataPerJenis['UTS']   * $bobot['bobot_uts']
                 + $rataPerJenis['UAS']   * $bobot['bobot_uas']) / 100;
    $nilaiAkhir = round($nilaiAkhir, 2);

    // Indeks kedisiplinan
    $tepatWaktu = 0; $sudahDinilai = 0;
    foreach ($nilaiSiswa as $n) {
        $sudahDinilai++;
        if ($n['status_pengumpulan'] === 'Tepat waktu') $tepatWaktu++;
    }
    $indeksKedisiplinan = ($totalPenilaianAda > 0)
        ? round(($tepatWaktu / $totalPenilaianAda) * 100, 2)
        : 0;

    // Status kelengkapan
    $statusKelengkapan = ($sudahDinilai >= $totalPenilaianAda && $totalPenilaianAda > 0)
        ? 'Lengkap' : 'Belum lengkap';

    // Kategori
    if ($nilaiAkhir >= 75 && $indeksKedisiplinan >= 75)     $kuadran = 'Kuadran I';
    elseif ($nilaiAkhir >= 75 && $indeksKedisiplinan < 75)  $kuadran = 'Kuadran II';
    elseif ($nilaiAkhir < 75 && $indeksKedisiplinan >= 75)  $kuadran = 'Kuadran III';
    else                                                     $kuadran = 'Kuadran IV';

    // Rekomendasi
    $rekomendasi = [
        'Kuadran I'   => 'Siswa berprestasi dan disiplin. Pertahankan dan berikan apresiasi.',
        'Kuadran II'  => 'Nilai baik namun kedisiplinan rendah. Perlu pembinaan perilaku.',
        'Kuadran III' => 'Disiplin baik namun nilai rendah. Perlu bimbingan akademik tambahan.',
        'Kuadran IV'  => 'Perlu perhatian khusus: bimbingan akademik dan pembinaan kedisiplinan.'
    ][$kuadran];

    $stmt = $pdo->prepare('SELECT id_rekap FROM REKAP_AKADEMIK_KUADRAN
        WHERE id_penugasan = ? AND id_siswa = ?');
    $stmt->execute([$idPenugasan, $idSiswa]);
    $existingRekap = $stmt->fetch();

    if ($existingRekap) {
        $stmt = $pdo->prepare('UPDATE REKAP_AKADEMIK_KUADRAN
            SET nilai_akhir = ?, indeks_kedisiplinan = ?,
                status_kelengkapan_nilai = ?, kategori_kuadran = ?, rekomendasi_sistem = ?
            WHERE id_rekap = ?');
        $stmt->execute([$nilaiAkhir, $indeksKedisiplinan, $statusKelengkapan,
            $kuadran, $rekomendasi, $existingRekap['id_rekap']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO REKAP_AKADEMIK_KUADRAN
            (id_penugasan, id_siswa, nilai_akhir, indeks_kedisiplinan,
             status_kelengkapan_nilai, kategori_kuadran, rekomendasi_sistem)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$idPenugasan, $idSiswa, $nilaiAkhir, $indeksKedisiplinan,
            $statusKelengkapan, $kuadran, $rekomendasi]);
    }

    $hasilRekap[] = [
        'id_siswa' => $idSiswa, 'nilai_akhir' => $nilaiAkhir,
        'indeks_kedisiplinan' => $indeksKedisiplinan,
        'status_kelengkapan_nilai' => $statusKelengkapan,
        'kategori_kuadran' => $kuadran, 'rekomendasi_sistem' => $rekomendasi
    ];
}
$pdo->commit();
respond($hasilRekap, 'Rekap berhasil dihitung untuk ' . count($hasilRekap) . ' siswa.');
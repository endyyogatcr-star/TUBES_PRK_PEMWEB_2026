<?php
require __DIR__ . '/bootstrap.php';

$idPenilaian = positiveId($_GET['id_penilaian'] ?? 0);

// Cek penilaian
$stmt = $pdo->prepare('SELECT p.*, pm.id_guru, pm.id_kelas
    FROM PENILAIAN p
    JOIN PENUGASAN_MENGAJAR pm ON pm.id_penugasan = p.id_penugasan
    WHERE p.id_penilaian = ?');
$stmt->execute([$idPenilaian]);
$penilaian = $stmt->fetch();
if (!$penilaian) respond(null, 'Penilaian tidak ditemukan.', 404);
if (!isAdmin() && (int)$penilaian['id_guru'] !== (int)$_SESSION['guru']['id_guru']) {
    respond(null, 'Anda tidak memiliki akses ke penilaian ini.', 403);
}

if ($method === 'GET') {
    // Tampilkan semua siswa di kelas
    $stmt = $pdo->prepare('SELECT s.id_siswa, s.nisn, s.nama_siswa, s.jenis_kelamin,
            np.id_nilai_pengumpulan, np.waktu_pengumpulan, np.status_pengumpulan, np.nilai
        FROM SISWA s
        LEFT JOIN NILAI_DAN_PENGUMPULAN np
            ON np.id_siswa = s.id_siswa AND np.id_penilaian = ?
        WHERE s.id_kelas = ?
        ORDER BY s.nama_siswa');
    $stmt->execute([$idPenilaian, $penilaian['id_kelas']]);
    respond($stmt->fetchAll());
}

if ($method === 'DELETE') {
    $id = positiveId($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id_nilai_pengumpulan FROM NILAI_DAN_PENGUMPULAN
        WHERE id_nilai_pengumpulan = ? AND id_penilaian = ?');
    $stmt->execute([$id, $idPenilaian]);
    if (!$stmt->fetch()) respond(null, 'Data nilai tidak ditemukan.', 404);
    $stmt = $pdo->prepare('DELETE FROM NILAI_DAN_PENGUMPULAN WHERE id_nilai_pengumpulan = ?');
    $stmt->execute([$id]);
    respond(null, 'Nilai berhasil dihapus.');
}

if (!in_array($method, ['POST', 'PUT'])) respond(null, 'Metode tidak didukung.', 405);

$input = inputData();

if (isset($input['batch']) && is_array($input['batch'])) {
    $pdo->beginTransaction();
    $inserted = 0; $updated = 0;
    foreach ($input['batch'] as $item) {
        if (!is_array($item)) respond(null, 'Format batch tidak valid.', 422);
        $idSiswa = positiveId($item['id_siswa'] ?? 0);
        $nilai = filter_var($item['nilai'] ?? '', FILTER_VALIDATE_FLOAT);
        $status = trim($item['status_pengumpulan'] ?? 'Belum dikumpulkan');

        if ($nilai === false || $nilai < 0 || $nilai > 100) {
            $pdo->rollBack();
            respond(null, "Nilai untuk siswa ID $idSiswa harus angka 0–100.", 422);
        }
        $statusValid = ['Tepat waktu', 'Terlambat', 'Belum dikumpulkan'];
        if (!in_array($status, $statusValid)) {
            $pdo->rollBack();
            respond(null, "Status pengumpulan tidak valid untuk siswa ID $idSiswa.", 422);
        }
        // Validasi kelas siswa
        $stmt = $pdo->prepare('SELECT id_siswa FROM SISWA WHERE id_siswa = ? AND id_kelas = ?');
        $stmt->execute([$idSiswa, $penilaian['id_kelas']]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            respond(null, "Siswa ID $idSiswa tidak terdaftar di kelas ini.", 422);
        }

        $waktu = ($status !== 'Belum dikumpulkan') ? date('Y-m-d H:i:s') : null;

        // cek apakah sudah ada nilai
        $stmt = $pdo->prepare('SELECT id_nilai_pengumpulan FROM NILAI_DAN_PENGUMPULAN
            WHERE id_siswa = ? AND id_penilaian = ?');
        $stmt->execute([$idSiswa, $idPenilaian]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare('UPDATE NILAI_DAN_PENGUMPULAN
                SET nilai = ?, status_pengumpulan = ?, waktu_pengumpulan = COALESCE(?, waktu_pengumpulan)
                WHERE id_nilai_pengumpulan = ?');
            $stmt->execute([$nilai, $status, $waktu, $existing['id_nilai_pengumpulan']]);
            $updated++;
        } else {
            $stmt = $pdo->prepare('INSERT INTO NILAI_DAN_PENGUMPULAN
                (id_siswa, id_penilaian, nilai, status_pengumpulan, waktu_pengumpulan)
                VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$idSiswa, $idPenilaian, $nilai, $status, $waktu]);
            $inserted++;
        }
    }
    $pdo->commit();
    respond(['inserted' => $inserted, 'updated' => $updated],
        "Batch selesai: $inserted baru, $updated diperbarui.");
}

$idSiswa = positiveId($input['id_siswa'] ?? 0);
$nilai = filter_var($input['nilai'] ?? '', FILTER_VALIDATE_FLOAT);
$status = trim($input['status_pengumpulan'] ?? 'Belum dikumpulkan');

if ($nilai === false || $nilai < 0 || $nilai > 100) {
    respond(null, 'Nilai harus berupa angka antara 0 sampai 100.', 422);
}
$statusValid = ['Tepat waktu', 'Terlambat', 'Belum dikumpulkan'];
if (!in_array($status, $statusValid)) {
    respond(null, 'Status pengumpulan harus: ' . implode(', ', $statusValid), 422);
}

// Pastikan siswa di kelas yang sesuai
$stmt = $pdo->prepare('SELECT id_siswa FROM SISWA WHERE id_siswa = ? AND id_kelas = ?');
$stmt->execute([$idSiswa, $penilaian['id_kelas']]);
if (!$stmt->fetch()) respond(null, 'Siswa tidak terdaftar di kelas penugasan ini.', 422);

$waktu = ($status !== 'Belum dikumpulkan') ? date('Y-m-d H:i:s') : null;

if ($method === 'PUT') {
    $id = positiveId($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id_nilai_pengumpulan FROM NILAI_DAN_PENGUMPULAN
        WHERE id_nilai_pengumpulan = ? AND id_penilaian = ?');
    $stmt->execute([$id, $idPenilaian]);
    if (!$stmt->fetch()) respond(null, 'Data nilai tidak ditemukan.', 404);

    $stmt = $pdo->prepare('UPDATE NILAI_DAN_PENGUMPULAN
        SET id_siswa = ?, nilai = ?, status_pengumpulan = ?, waktu_pengumpulan = COALESCE(?, waktu_pengumpulan)
        WHERE id_nilai_pengumpulan = ?');
    $stmt->execute([$idSiswa, $nilai, $status, $waktu, $id]);
    respond(['id_nilai_pengumpulan' => $id], 'Nilai berhasil diperbarui.');
} else {
    // Tolak duplikat siswa
    $stmt = $pdo->prepare('SELECT id_nilai_pengumpulan FROM NILAI_DAN_PENGUMPULAN
        WHERE id_siswa = ? AND id_penilaian = ?');
    $stmt->execute([$idSiswa, $idPenilaian]);
    if ($stmt->fetch()) respond(null, 'Nilai untuk siswa ini sudah tercatat. Gunakan mode edit.', 409);

    $stmt = $pdo->prepare('INSERT INTO NILAI_DAN_PENGUMPULAN
        (id_siswa, id_penilaian, nilai, status_pengumpulan, waktu_pengumpulan)
        VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$idSiswa, $idPenilaian, $nilai, $status, $waktu]);
    respond(['id_nilai_pengumpulan' => (int)$pdo->lastInsertId()], 'Nilai berhasil disimpan.', 201);
}
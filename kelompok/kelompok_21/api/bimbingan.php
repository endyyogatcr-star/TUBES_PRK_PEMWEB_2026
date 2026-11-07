<?php
require __DIR__ . '/bootstrap.php';

$idGuru = $_SESSION['guru']['id_guru'];

if ($method === 'GET') {
    // filter berdasarkan id_siswa atau id_periode
    $conditions = []; $params = [];

    if (!isAdmin()) {
        $conditions[] = 'c.id_guru = ?';
        $params[] = $idGuru;
    }
    if (!empty($_GET['id_siswa'])) {
        $conditions[] = 'c.id_siswa = ?';
        $params[] = positiveId($_GET['id_siswa']);
    }
    if (!empty($_GET['id_periode'])) {
        $conditions[] = 'c.id_periode = ?';
        $params[] = positiveId($_GET['id_periode']);
    }

    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $stmt = $pdo->prepare("SELECT c.*, s.nama_siswa, s.nisn, g.nama_guru, p.tahun_ajaran, p.semester
        FROM CATATAN_BIMBINGAN c
        JOIN SISWA s ON s.id_siswa = c.id_siswa
        JOIN GURU g ON g.id_guru = c.id_guru
        JOIN PERIODE_PENILAIAN p ON p.id_periode = c.id_periode
        $where
        ORDER BY c.tanggal_interaksi DESC, c.id_catatan DESC");
    $stmt->execute($params);
    respond($stmt->fetchAll());
}

if ($method === 'DELETE') {
    $id = positiveId($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM CATATAN_BIMBINGAN WHERE id_catatan = ?');
    $stmt->execute([$id]);
    $catatan = $stmt->fetch();
    if (!$catatan) respond(null, 'Catatan tidak ditemukan.', 404);
    if (!isAdmin() && (int)$catatan['id_guru'] !== (int)$idGuru) {
        respond(null, 'Anda hanya dapat menghapus catatan milik sendiri.', 403);
    }
    $stmt = $pdo->prepare('DELETE FROM CATATAN_BIMBINGAN WHERE id_catatan = ?');
    $stmt->execute([$id]);
    respond(null, 'Catatan bimbingan berhasil dihapus.');
}

if (!in_array($method, ['POST', 'PUT'])) respond(null, 'Metode tidak didukung.', 405);

$input = inputData();

$idPeriode = positiveId($input['id_periode'] ?? 0);
$idSiswa   = positiveId($input['id_siswa'] ?? 0);
$catatan   = trim($input['catatan_kualitatif'] ?? '');
$rencana   = trim($input['rencana_tindakan'] ?? '');
$statusTL  = trim($input['status_tindak_lanjut'] ?? 'Belum ditindaklanjuti');
$evaluasi  = trim($input['evaluasi_hasil'] ?? '');
$tanggal   = trim($input['tanggal_interaksi'] ?? '');

if (!$catatan) respond(null, 'Catatan kualitatif wajib diisi.', 422);
if (!$rencana) respond(null, 'Rencana tindakan wajib diisi.', 422);

$statusTLValid = ['Belum ditindaklanjuti', 'Sedang berjalan', 'Selesai'];
if (!in_array($statusTL, $statusTLValid)) {
    respond(null, 'Status tindak lanjut harus: ' . implode(', ', $statusTLValid), 422);
}

// Validasi tanggal
$tglObj = date_create($tanggal);
if (!$tglObj) {
    respond(null, 'Format tanggal interaksi tidak valid.', 422);
}
$tanggalFormatted = $tglObj->format('Y-m-d H:i:s');

// Cek periode
$stmt = $pdo->prepare('SELECT id_periode FROM PERIODE_PENILAIAN WHERE id_periode = ?');
$stmt->execute([$idPeriode]);
if (!$stmt->fetch()) respond(null, 'Periode penilaian tidak ditemukan.', 404);

// Cek siswa
$stmt = $pdo->prepare('SELECT id_siswa FROM SISWA WHERE id_siswa = ?');
$stmt->execute([$idSiswa]);
if (!$stmt->fetch()) respond(null, 'Siswa tidak ditemukan.', 404);

if ($method === 'PUT') {
    $id = positiveId($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM CATATAN_BIMBINGAN WHERE id_catatan = ?');
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) respond(null, 'Catatan tidak ditemukan.', 404);
    if (!isAdmin() && (int)$old['id_guru'] !== (int)$idGuru) {
        respond(null, 'Anda hanya dapat mengedit catatan milik sendiri.', 403);
    }

    $stmt = $pdo->prepare('UPDATE CATATAN_BIMBINGAN
        SET id_periode = ?, id_siswa = ?, catatan_kualitatif = ?,
            rencana_tindakan = ?, status_tindak_lanjut = ?,
            evaluasi_hasil = ?, tanggal_interaksi = ?
        WHERE id_catatan = ?');
    $stmt->execute([$idPeriode, $idSiswa, $catatan, $rencana, $statusTL,
        $evaluasi, $tanggalFormatted, $id]);
    respond(['id_catatan' => $id], 'Catatan bimbingan berhasil diperbarui.');
} else {
    $stmt = $pdo->prepare('INSERT INTO CATATAN_BIMBINGAN
        (id_periode, id_guru, id_siswa, catatan_kualitatif, rencana_tindakan,
         status_tindak_lanjut, evaluasi_hasil, tanggal_interaksi)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idPeriode, $idGuru, $idSiswa, $catatan, $rencana,
        $statusTL, $evaluasi, $tanggalFormatted]);
    respond(['id_catatan' => (int)$pdo->lastInsertId()], 'Catatan bimbingan berhasil disimpan.', 201);
}
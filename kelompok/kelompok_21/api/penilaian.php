<?php
require __DIR__ . '/bootstrap.php';

$idPenugasan = positiveId($_GET['id_penugasan'] ?? 0);

// Validasi kepemilikan penugasan
$stmt = $pdo->prepare('SELECT * FROM PENUGASAN_MENGAJAR WHERE id_penugasan = ?');
$stmt->execute([$idPenugasan]);
$penugasan = $stmt->fetch();
if (!$penugasan) respond(null, 'Penugasan tidak ditemukan.', 404);
if (!isAdmin() && (int)$penugasan['id_guru'] !== (int)$_SESSION['guru']['id_guru']) {
    respond(null, 'Anda tidak memiliki akses ke penugasan ini.', 403);
}

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT * FROM PENILAIAN WHERE id_penugasan = ? ORDER BY tenggat_waktu ASC, id_penilaian DESC');
    $stmt->execute([$idPenugasan]);
    respond($stmt->fetchAll());
}

if ($method === 'DELETE') {
    $id = positiveId($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id_penilaian FROM PENILAIAN WHERE id_penilaian = ? AND id_penugasan = ?');
    $stmt->execute([$id, $idPenugasan]);
    if (!$stmt->fetch()) respond(null, 'Penilaian tidak ditemukan.', 404);
    $stmt = $pdo->prepare('DELETE FROM PENILAIAN WHERE id_penilaian = ?');
    $stmt->execute([$id]);
    respond(null, 'Penilaian berhasil dihapus.');
}

if (!in_array($method, ['POST', 'PUT'])) respond(null, 'Metode tidak didukung.', 405);

$input = inputData();
$judul = trim($input['judul_penilaian'] ?? '');
$jenis = trim($input['jenis_komponen'] ?? '');
$tenggat = trim($input['tenggat_waktu'] ?? '');

if (!$judul || mb_strlen($judul) > 100) {
    respond(null, 'Judul penilaian wajib diisi, maksimal 100 karakter.', 422);
}
$jenisValid = ['Tugas', 'Kuis', 'UTS', 'UAS'];
if (!in_array($jenis, $jenisValid)) {
    respond(null, 'Jenis komponen harus salah satu dari: ' . implode(', ', $jenisValid), 422);
}
// Validasi format datetime
$tanggalTenggat = date_create($tenggat);
if (!$tanggalTenggat) {
    respond(null, 'Format tenggat waktu tidak valid. Gunakan format YYYY-MM-DD HH:MM.', 422);
}
$tenggatFormatted = $tanggalTenggat->format('Y-m-d H:i:s');

if ($method === 'PUT') {
    $id = positiveId($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id_penilaian FROM PENILAIAN WHERE id_penilaian = ? AND id_penugasan = ?');
    $stmt->execute([$id, $idPenugasan]);
    if (!$stmt->fetch()) respond(null, 'Penilaian tidak ditemukan.', 404);

    $stmt = $pdo->prepare('UPDATE PENILAIAN
        SET judul_penilaian = ?, jenis_komponen = ?, tenggat_waktu = ?
        WHERE id_penilaian = ?');
    $stmt->execute([$judul, $jenis, $tenggatFormatted, $id]);
    respond(['id_penilaian' => $id], 'Penilaian berhasil diperbarui.');
} else {
    $stmt = $pdo->prepare('INSERT INTO PENILAIAN
        (id_penugasan, judul_penilaian, jenis_komponen, tenggat_waktu, tanggal_dibuat)
        VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$idPenugasan, $judul, $jenis, $tenggatFormatted]);
    respond(['id_penilaian' => (int)$pdo->lastInsertId()], 'Penilaian berhasil dibuat.', 201);
}
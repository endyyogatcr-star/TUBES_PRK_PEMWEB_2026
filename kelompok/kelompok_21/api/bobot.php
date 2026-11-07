<?php
require __DIR__ . '/bootstrap.php';

$idPenugasan = positiveId($_GET['id_penugasan'] ?? 0);

// Cek kepemilikan penugasan
$stmt = $pdo->prepare('SELECT * FROM PENUGASAN_MENGAJAR WHERE id_penugasan = ?');
$stmt->execute([$idPenugasan]);
$penugasan = $stmt->fetch();
if (!$penugasan) respond(null, 'Penugasan tidak ditemukan.', 404);
if (!isAdmin() && (int)$penugasan['id_guru'] !== (int)$_SESSION['guru']['id_guru']) {
    respond(null, 'Anda tidak memiliki akses ke penugasan ini.', 403);
}

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT * FROM PENGATURAN_BOBOT WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $bobot = $stmt->fetch();
    respond($bobot ?: null);
}

if (!in_array($method, ['POST', 'PUT'])) respond(null, 'Metode tidak didukung.', 405);

$input = inputData();
$bobotTugas = filter_var($input['bobot_tugas'] ?? '', FILTER_VALIDATE_FLOAT);
$bobotKuis  = filter_var($input['bobot_kuis'] ?? '', FILTER_VALIDATE_FLOAT);
$bobotUts   = filter_var($input['bobot_uts'] ?? '', FILTER_VALIDATE_FLOAT);
$bobotUas   = filter_var($input['bobot_uas'] ?? '', FILTER_VALIDATE_FLOAT);

if ($bobotTugas === false || $bobotKuis === false || $bobotUts === false || $bobotUas === false) {
    respond(null, 'Semua bobot harus berupa angka.', 422);
}
if ($bobotTugas < 0 || $bobotKuis < 0 || $bobotUts < 0 || $bobotUas < 0) {
    respond(null, 'Bobot tidak boleh negatif.', 422);
}

$total = $bobotTugas + $bobotKuis + $bobotUts + $bobotUas;
if (abs($total - 100) > 0.01) {
    respond(null, 'Total bobot harus tepat 100. Saat ini: ' . round($total, 2), 422);
}

// Cek apakah bobot sudah ada untuk penugasan
$stmt = $pdo->prepare('SELECT id_bobot FROM PENGATURAN_BOBOT WHERE id_penugasan = ?');
$stmt->execute([$idPenugasan]);
$existing = $stmt->fetch();

if ($existing) {
    // Update bobot yang sudah ada
    $stmt = $pdo->prepare('UPDATE PENGATURAN_BOBOT
        SET bobot_tugas = ?, bobot_kuis = ?, bobot_uts = ?, bobot_uas = ?
        WHERE id_penugasan = ?');
    $stmt->execute([$bobotTugas, $bobotKuis, $bobotUts, $bobotUas, $idPenugasan]);
    respond(['id_bobot' => (int)$existing['id_bobot']], 'Bobot berhasil diperbarui.');
} else {
    // Buat bobot baru
    $stmt = $pdo->prepare('INSERT INTO PENGATURAN_BOBOT
        (id_penugasan, bobot_tugas, bobot_kuis, bobot_uts, bobot_uas)
        VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$idPenugasan, $bobotTugas, $bobotKuis, $bobotUts, $bobotUas]);
    respond(['id_bobot' => (int)$pdo->lastInsertId()], 'Bobot berhasil disimpan.', 201);
}
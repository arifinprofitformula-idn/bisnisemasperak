<?php
include('fungsi.php');
// Ambil data JSON yang dikirim oleh AJAX
$data = json_decode(file_get_contents('php://input'), true);
if ($data) {
    $module = $data['settings']['moduleId'];  // Jenis modul yang dipilih
    $moduleId = $data['settings']['canvasId'];
    $settingmodul = array(
        'modul' => $module,
        'data' => $data['settings']);  // Pengaturan modul yang diterima

    // Tentukan file tempat menyimpan pengaturan modul
    $setmodule = $moduleId;
    $newsettings[$setmodule] = json_encode($settingmodul);

    // Simpan pengaturan ke file database
    $settings = updatesettings($newsettings);

    // Beri respon sukses
    echo json_encode(['status' => 'success', 'message' => 'Pengaturan berhasil disimpan']);
} else {
    // Jika data tidak valid, kirim respon error
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan pengaturan']);
    print_r($data);
}
?>

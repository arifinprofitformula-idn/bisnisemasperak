# Laporan Insiden & Panduan Perbaikan Sistem Komisi EPI-OSS

**Tanggal Dokumen:** 05 Februari 2026  
**Penyusun:** Arva EPI OSS Builder  
**Status:** Resolved (Selesai Diperbaiki)  
**Lingkup:** Modul Keuangan (Komisi, Wallet, Payout)

---

## 1. Ringkasan Masalah (Executive Summary)

Ditemukan ketidaksesuaian antara **Total Komisi Diperoleh** dengan **Saldo Siap Cair** pada dashboard member.
- **Gejala:** User merasa saldonya berkurang lebih banyak daripada uang yang diterima.
- **Contoh Kasus:** User mencairkan Rp 1.600.000, namun saldo di sistem berkurang Rp 3.200.000 (2x lipat).
- **Dampak:** Ketidakpercayaan mitra/member terhadap transparansi sistem keuangan.

## 2. Analisis Akar Masalah (Root Cause Analysis)

### A. Double Ledger Entry (Pencatatan Ganda)
Sistem lama tidak memiliki proteksi *idempotency* (pencegahan duplikasi) yang ketat saat memproses pencairan (`payout`).
- Saat admin menekan tombol "Bayar" atau sistem memproses otomatis, terjadi *race condition* atau eksekusi ganda.
- Akibatnya, tabel `sa_laporan` (buku besar) mencatat pengeluaran (Debit) sebanyak 2 kali atau lebih untuk satu ID pencairan yang sama.

### B. Kesalahan Logika Tampilan Dashboard
Halaman dashboard (`dashmemberkomisi.php`) menghitung saldo dengan rumus:
`Saldo = (Total Masuk - Total Keluar di Ledger) - (Total Request di Tabel Payout)`
Karena "Total Keluar di Ledger" sudah mencatat status 'Paid', dan "Total Request" juga menghitung status 'Paid', maka terjadi pengurangan ganda (double counting) secara visual, meskipun datanya mungkin benar di database (sebelum kasus poin A terjadi).

---

## 3. Prosedur Pra-Eksekusi (Backup & Persiapan)

Sebelum melakukan perbaikan di Live Server (cPanel), **WAJIB** lakukan langkah berikut:

1.  **Backup Database (via phpMyAdmin):**
    - Masuk cPanel > phpMyAdmin.
    - Pilih database `bisnisemasperak` (atau nama db live).
    - Klik tab **Export** > Format: SQL > Klik **Go**.
    - Simpan file `.sql` di komputer lokal sebagai `db_backup_pre_fix.sql`.

2.  **Backup File Website (via File Manager):**
    - Masuk cPanel > File Manager.
    - Compress folder `theme/simple` dan file root utama menjadi `.zip`.
    - Download file zip tersebut.

---

## 4. Langkah Implementasi Perbaikan (Step-by-Step)

### Tahap 1: Upload Script Diagnosa & Perbaikan
Kita membutuhkan alat bantu untuk membersihkan data yang rusak.

1.  Buka **File Manager** di cPanel.
2.  Masuk ke `public_html` (root folder aplikasi).
3.  Buat file baru bernama `fix_commission_data.php`.
4.  Isi dengan kode berikut (Script ini dimodifikasi agar aman dijalankan via Browser untuk kemudahan):

```php
<?php
// fix_commission_data.php - Web Version
require_once 'config.php';
require_once 'fungsi.php';

// Proteksi sederhana (Ganti 'RAHASIA123' dengan password sementara Anda)
if (!isset($_GET['key']) || $_GET['key'] !== 'RAHASIA123') {
    die("Akses ditolak.");
}

echo "<pre>Mulai Perbaikan Data Komisi...\n";
echo "--------------------------------------------------\n";

// 1. Hapus Duplikat (Pembersihan Data)
// Mencari entri di sa_laporan yang 'yatim piatu' (tidak punya link payout_id tapi deskripsinya pencairan)
$sqlOrphans = "SELECT * FROM sa_laporan 
               WHERE lap_code IN (2,3) 
               AND lap_keluar > 0 
               AND payout_id IS NULL 
               AND (lap_keterangan LIKE '%Pencairan Komisi%' OR lap_keterangan LIKE '%Potongan PPh21%')";

$orphans = db_select($sqlOrphans);
$deleted = 0;
foreach ($orphans as $o) {
    // Hapus baris ini
    db_query("DELETE FROM sa_laporan WHERE lap_id=" . $o['lap_id']);
    $deleted++;
    echo "Deleted Orphan ID: " . $o['lap_id'] . " (User: " . $o['lap_idsponsor'] . ", Rp " . number_format($o['lap_keluar']) . ")\n";
}

echo "--------------------------------------------------\n";
echo "Total Data Sampah Dibersihkan: $deleted baris.\n";
echo "Perbaikan Selesai.</pre>";
?>
```

### Tahap 2: Eksekusi Perbaikan Data
1.  Buka browser, akses: `https://domainanda.com/fix_commission_data.php?key=RAHASIA123`
2.  Tunggu hingga muncul pesan "Perbaikan Selesai".
3.  Simpan log yang muncul di layar (copy-paste ke notepad) sebagai bukti perbaikan.
4.  **PENTING:** Segera **HAPUS** file `fix_commission_data.php` dari File Manager setelah selesai agar tidak disalahgunakan.

### Tahap 3: Patching Kode Tampilan (Dashboard)
Memperbaiki logika perhitungan agar tidak membingungkan member.

1.  Di cPanel File Manager, edit file: `theme/simple/dashmemberkomisi.php`.
2.  Cari bagian perhitungan saldo (sekitar baris 18-30).
3.  Ganti blok kode lama dengan logika baru ini:

```php
// REVISI LOGIKA SALDO:
// 1. Ambil Saldo Real dari Buku Besar (Masuk - Keluar)
$saldoRowS = db_row("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code` IN (2)");
$saldoRowC = db_row("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code` IN (3)");
$komisiS = isset($saldoRowS['komisi']) ? (int)$saldoRowS['komisi'] : 0;
$komisiC = isset($saldoRowC['komisi']) ? (int)$saldoRowC['komisi'] : 0;

// 2. Hitung yang sedang di-HOLD (Requested/Pending/Processed)
// PENTING: Jangan sertakan status 'paid' di sini karena sudah tercatat di 'lap_keluar' di atas.
$reservedS = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." AND `type`='sponsor' AND `status` IN ('requested','pending','processed')");
$reservedC = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." AND `type`='contrib' AND `status` IN ('requested','pending','processed')");

// 3. Saldo Tersedia = Saldo Buku Besar - Hold
$saldoAvailSponsor = max(0, $komisiS - $reservedS);
$saldoAvailContrib = max(0, $komisiC - $reservedC);
$saldoAvailableTotal = $saldoAvailSponsor + $saldoAvailContrib;
```

---

## 5. Testing & Verifikasi (Quality Assurance)

Setelah perbaikan, lakukan pengujian berikut:

| Skenario | Langkah Test | Hasil yang Diharapkan |
| :--- | :--- | :--- |
| **Cek Saldo Member** | Login sebagai member yang sebelumnya bermasalah. Buka menu Komisi. | Saldo "Siap Cair" + "Sudah Dicairkan" ≈ Total Pendapatan. Tidak ada angka minus. |
| **Simulasi Withdraw** | Lakukan request withdraw baru (nominal kecil). | Saldo berkurang di tampilan "Siap Cair", pindah ke "Sedang Diproses". |
| **Cek Database** | Cek tabel `epi_commission_payout` status 'requested'. | Data masuk 1 baris baru. |
| **Proses Bayar (Admin)** | Login Admin > Manage > Bayar Komisi. Proses withdraw tadi. | Status berubah jadi 'Paid'. Di `sa_laporan` muncul 1 baris debit baru. |
| **Cek Duplikasi** | Cek `sa_laporan` lagi untuk ID user tersebut. | Pastikan debit HANYA tercatat 1 kali untuk transaksi barusan. |

---

## 6. Riwayat Perubahan File (Changelog)

Berikut adalah daftar file yang mengalami perubahan dalam patch ini:

1.  **`theme/simple/dashmemberkomisi.php`**
    - **Perubahan:** Update rumus `$saldoAvail` dan desain UI Card Dashboard.
    - **Tujuan:** Transparansi saldo (memisahkan Saldo Real, Hold, dan Paid).

2.  **`theme/simple/dashbayar.php`** (Sudah dilakukan di tahap sebelumnya)
    - **Perubahan:** Penambahan `START TRANSACTION`, `COMMIT`, dan validasi unik `lap_reference`.
    - **Tujuan:** Mencegah *Double Ledger Entry* di masa depan.

3.  **Database Schema**
    - **Tabel:** `epi_commission_payout`
    - **Perubahan:** Penambahan kolom `reject_reason`, `cancel_reason`, `canceled_at`.

---

## 7. Kontak Support

Jika masalah berlanjut atau terjadi error 500 setelah penerapan:
1.  Restore file `dashmemberkomisi.php` dari backup.
2.  Hubungi tim teknis Arva EPI OSS Builder.

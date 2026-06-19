<?php
// Ensure this file is accessed within the framework
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }

// Initialize security
require_once __DIR__ . '/../../class/EpichubSecurity.php';
$security = new EpichubSecurity();

// --- KONFIGURASI PRODUK ---
// Masukkan ID Produk yang memberikan akses ke fitur ini.
// Contoh: [13, 15] untuk Batch 3 dan Batch 4.
// Biarkan kosong [] jika akses terbuka untuk semua member aktif.
$requiredProducts = [19]; 
$security->setRequiredProducts($requiredProducts);
// --------------------------

$errorMsg = '';
$accessGranted = false;

try {
    // Basic login check (redundant if menudata.php enforces it, but safe)
    if (!isset($datamember) || empty($datamember)) {
        header("Location: " . $weburl . "login");
        exit;
    }

    // Perform strict validation
    $accessGranted = $security->validateAccess($datamember);

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    // Logging is handled inside validateAccess
}

// Prepare header data
$head['pagetitle'] = "Akses AI CONTENT MAKER";
$head['description'] = "Halaman akses khusus member Epic Hub untuk tools AI CONTENT MAKER.";

// Load header
showheader($head);
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-primary text-white text-center py-4" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                    <h3 class="mb-0"><i class="fas fa-robot me-2"></i> AI CONTENT MAKER</h3>
                </div>
                <div class="card-body p-5 text-center">
                    
                    <?php if ($accessGranted): ?>
                        <div class="alert alert-success mb-4">
                            <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Akses Diterima</h4>
                            <p>Selamat datang, <strong><?= htmlspecialchars($datamember['mem_nama']) ?></strong>! Status membership Anda valid.</p>
                        </div>

                        <p class="lead mb-4">
                            Anda memiliki akses eksklusif ke <strong>AI CONTENT MAKER</strong> powered by Gemini.
                            Gunakan tools ini untuk membuat konten marketing yang powerful dengan cepat.
                        </p>

                        <div class="d-grid gap-2 col-md-8 mx-auto">
                            <a href="<?= $weburl ?>epichub/gemini.php" target="_blank" class="btn btn-warning btn-lg fw-bold text-dark py-3 shadow-sm hover-scale" style="background-color: #ffc107; border-color: #ffc107;">
                                <i class="fas fa-external-link-alt me-2"></i> AKSES AI CONTENT MAKER
                            </a>
                        </div>
                        
                        <p class="text-muted mt-4 small">
                            <i class="fas fa-info-circle"></i> Link ini hanya untuk member terverifikasi. Jangan bagikan ke pihak luar.
                        </p>

                    <?php else: ?>
                        
                        <div class="alert alert-danger mb-4">
                            <h4 class="alert-heading"><i class="fas fa-lock"></i> Akses Ditolak</h4>
                            <p><?= htmlspecialchars($errorMsg) ?></p>
                        </div>

                        <p class="text-muted">
                            Maaf, akun Anda tidak memiliki izin untuk mengakses fitur ini.
                            Jika Anda merasa ini adalah kesalahan, silakan hubungi admin atau upgrade membership Anda.
                        </p>
                        
                        <a href="<?= $weburl ?>dashboard" class="btn btn-secondary mt-3">
                            <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                        </a>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-scale { transition: transform 0.2s; }
.hover-scale:hover { transform: scale(1.05); }
</style>

<?php showfooter(); ?>

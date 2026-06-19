<?php
// epichub/gemini.php - Redirect script for AI CONTENT MAKER

// Change directory to root so relative includes work
chdir(__DIR__ . '/../');

// Include core configuration and functions
require_once 'config.php';
require_once 'fungsi.php';

// Include security class
require_once 'class/EpichubSecurity.php';

// Check if user is logged in
$id_member = is_login();
if (!$id_member) {
    header("Location: " . $weburl . "login");
    exit();
}

// Get member data
$datamember = getdatamember($id_member);

// Initialize security
$security = new EpichubSecurity();

// Set required products (Same as in epichub_access.php)
// [13, 15] for Batch 3/4, [19] for AI Content Maker
$requiredProducts = [19]; 
$security->setRequiredProducts($requiredProducts);

try {
    // Validate access
    $security->validateAccess($datamember);

    // If successful, redirect to the Gemini link
    header("Location: https://gemini.google.com/share/7e0614c2a7f7");
    exit();

} catch (Exception $e) {
    // If access denied, show error page or redirect
    $errorMsg = $e->getMessage();
    
    // Simple error output with link back to dashboard
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Akses Ditolak - AI CONTENT MAKER</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="../bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .card { max-width: 500px; width: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="card-body text-center p-5">
                <div class="mb-4 text-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                      <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                      <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
                <h3 class="card-title mb-3">Akses Ditolak</h3>
                <p class="card-text text-muted mb-4">' . htmlspecialchars($errorMsg) . '</p>
                <a href="' . $weburl . 'dashboard" class="btn btn-primary">Kembali ke Dashboard</a>
            </div>
        </div>
    </body>
    </html>';
    exit();
}
?>
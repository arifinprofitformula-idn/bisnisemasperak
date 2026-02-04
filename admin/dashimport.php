<?php
// Duplicate download_template handler removed; handled in theme/simple/dashimport.php
# path: admin/dashimport.php
# Import Data Member via CSV - Admin Access

// Include konfigurasi dan fungsi utama
include '../config.php';
include '../fungsi.php';
include '../PasswordHash.php';

// Cek session dan login
session_start();
if (!isset($_SESSION['sauser']) || empty($_SESSION['sauser'])) {
    header('Location: ../index.php');
    exit;
}

// Ambil data user
$username = $_SESSION['sauser'];
$query = "SELECT * FROM sa_member WHERE mem_kodeaff = '" . cek($username) . "'";
$result = mysqli_query($con, $query);
$datauser = mysqli_fetch_array($result);

// Cek akses admin/staff
if ($datauser['mem_role'] < 5) {
    header('Location: ../dashboard');
    exit;
}

// Duplicate download_template handler removed; handled in theme/simple/dashimport.php

// Set variabel untuk template
$weburl = $setting['weburl'];
$dashfile = $weburl . 'admin/';

// Include halaman import dari theme
include '../theme/simple/dashimport.php';
?>
<?php
$weburl = "https://domainanda.com/";
$dbhost = "localhost";
$dbname = "database_name";
$dbuser = "database_user";
$dbpassword = "database_password"; // Jangan gunakan karakter $

define('SECRET', "change-me");

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') {
    header("Location:" . $weburl);
}

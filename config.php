<?php

$weburl = "http://bisnisemasperak.test/";

$dbhost     = "localhost";

$dbname     = "migrasibepi";

$dbuser     = "root";

$dbpassword = ""; # Jangan gunakan karakter $

define('SECRET', "c5JuOdQl3xpPml5uZRwc4rW7uPfIBnX4");

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') { 

	header("Location:".$weburl);

}

?>
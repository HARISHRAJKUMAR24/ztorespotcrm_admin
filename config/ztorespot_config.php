<?php

$ZT_DBH  = "mysql:host=localhost;dbname=ztorespot;charset=utf8";
$ZT_USER = "root";
$ZT_PASS = "";

try {
    $ztorespot_db = new PDO($ZT_DBH, $ZT_USER, $ZT_PASS);

    $ztorespot_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $ztorespot_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Ztorespot DB Connection Failed: " . $e->getMessage());
    die("Ztorespot Database Connection Failed.");
}
?>
<?php
session_start();
require_once '/usr/local/appserver/lib/dns_utils.php';

if (!isset($_SESSION['loggedin'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['add_dns_zone'])) {
    DNSUtils::addZone($_POST['zone'], $_POST['ip'], $_POST['ttl']);
}

if (isset($_POST['edit_dns_zone'])) {
    DNSUtils::editZone($_POST['zone'], $_POST['record']);
}

if (isset($_POST['delete_dns_zone'])) {
    DNSUtils::deleteZone($_POST['zone']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>DNS Zone Manager</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .section { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>DNS Zone Manager</h1>

    <div class="section">
        <h3>Add DNS Zone</h3>
        <form method="POST">
            Zone Adı: <input type="text" name="zone"><br>
            IP Adresi: <input type="text" name="ip"><br>
            TTL: <input type="number" name="ttl" value="86400"><br>
            <input type="submit" name="add_dns_zone" value="Ekle">
        </form>
    </div>

    <div class="section">
        <h3>Edit DNS Zone</h3>
        <form method="POST">
            Zone Adı: <input type="text" name="zone"><br>
            Kayıt: <input type="text" name="record"><br>
            <input type="submit" name="edit_dns_zone" value="Düzenle">
        </form>
    </div>

    <div class="section">
        <h3>Delete DNS Zone</h3>
        <form method="POST">
            Zone Adı: <input type="text" name="zone"><br>
            <input type="submit" name="delete_dns_zone" value="Sil">
        </form>
    </div>

    <a href="index.php">Geri Dön</a>
</body>
</html>
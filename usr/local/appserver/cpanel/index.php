<?php
session_start();
require_once '/usr/local/appserver/lib/config.php';
require_once '/usr/local/appserver/lib/user_manager.php';

if (!isset($_SESSION['cpanel_user'])) {
    if (isset($_POST['login'])) {
        if (UserManager::login($_POST['username'], $_POST['password'])) {
            $_SESSION['cpanel_user'] = $_POST['username'];
        } else {
            echo "<p style='color:red;'>Geçersiz giriş!</p>";
        }
    }
    ?>
    <h1>Appserver cPanel Giriş</h1>
    <form method="POST">
        Kullanıcı Adı: <input type="text" name="username"><br>
        Şifre: <input type="password" name="password"><br>
        <input type="submit" name="login" value="Giriş Yap">
    </form>
    <?php
    exit;
}

$user_dir = "/home/" . $_SESSION['cpanel_user'] . "/public_html";
$domain = $_SERVER['HTTP_HOST'];

$dir = $user_dir;
if (isset($_GET['dir']) && strpos($_GET['dir'], $user_dir) === 0) $dir = $_GET['dir'];
if (isset($_FILES['upload'])) {
    move_uploaded_file($_FILES['upload']['tmp_name'], $dir . "/" . $_FILES['upload']['name']);
}
if (isset($_POST['delete_file'])) {
    unlink($_POST['delete_file']);
}
if (isset($_POST['edit_file'])) {
    file_put_contents($_POST['edit_file'], $_POST['file_content']);
}
if (isset($_POST['zip_file'])) {
    exec("zip -r $dir/backup.zip $dir");
}

if (isset($_POST['create_email'])) {
    $email = $_POST['email'] . "@$domain";
    $pass = $_POST['email_pass'];
    exec("useradd -m -s /sbin/nologin $email");
    exec("echo '$pass' | passwd --stdin $email");
    mkdir("/home/$email/Maildir", 0755, true);
    exec("chown -R $email:$email /home/$email");
    $users = Config::get('/usr/local/appserver/data/users.json');
    $users[$email] = password_hash($pass, PASSWORD_DEFAULT);
    Config::set('/usr/local/appserver/data/users.json', $users);
}

if (isset($_POST['create_db'])) {
    $db_name = $_SESSION['cpanel_user'] . "_" . $_POST['db_name'];
    $pdo = new PDO("mysql:host=localhost", "root", "rootpassword");
    $pdo->exec("CREATE DATABASE `$db_name`");
    $pdo->exec("CREATE USER '$db_name'@'localhost' IDENTIFIED BY '{$_POST['db_pass']}'");
    $pdo->exec("GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_name'@'localhost'");
}

if (isset($_POST['create_subdomain'])) {
    $sub = $_POST['subdomain'] . "." . $domain;
    $sub_dir = $user_dir . "/" . $_POST['subdomain'];
    mkdir($sub_dir, 0755);
    $vhost = "<VirtualHost *:80>\n    ServerName $sub\n    DocumentRoot $sub_dir\n</VirtualHost>";
    file_put_contents("/etc/httpd/conf.d/sub_$sub.conf", $vhost);
    exec("systemctl restart httpd || systemctl restart apache2");
}

if (isset($_POST['create_addon'])) {
    $addon = $_POST['addon_domain'];
    $addon_dir = $user_dir . "/" . $_POST['addon_sub'];
    mkdir($addon_dir, 0755);
    $vhost = "<VirtualHost *:80>\n    ServerName $addon\n    DocumentRoot $addon_dir\n</VirtualHost>";
    file_put_contents("/etc/httpd/conf.d/addon_$addon.conf", $vhost);
    exec("systemctl restart httpd || systemctl restart apache2");
}

if (isset($_POST['park_domain'])) {
    $park = $_POST['park_domain'];
    $vhost = "<VirtualHost *:80>\n    ServerName $park\n    ServerAlias $domain\n    DocumentRoot $user_dir\n</VirtualHost>";
    file_put_contents("/etc/httpd/conf.d/park_$park.conf", $vhost);
    exec("systemctl restart httpd || systemctl restart apache2");
}

if (isset($_POST['create_ftp'])) {
    $ftp_user = $_POST['ftp_user'];
    $ftp_pass = $_POST['ftp_pass'];
    exec("useradd -m -d $user_dir -s /sbin/nologin $ftp_user");
    exec("echo '$ftp_pass' | passwd --stdin $ftp_user");
}

if (isset($_POST['generate_ssh'])) {
    $key_file = "/home/" . $_SESSION['cpanel_user'] . "/.ssh/id_rsa";
    exec("ssh-keygen -t rsa -f $key_file -N ''");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appserver cPanel</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .section { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Appserver cPanel</h1>
    <p>Hoş geldiniz, <?php echo $_SESSION['cpanel_user']; ?>!</p>

    <div class="section">
        <h3>Dosya Yöneticisi</h3>
        <p>Dizin: <?php echo htmlspecialchars($dir); ?></p>
        <form enctype="multipart/form-data" method="POST">
            <input type="file" name="upload"> <input type="submit" value="Yükle">
        </form>
        <form method="POST">
            <input type="submit" name="zip_file" value="Dizini Sıkıştır">
        </form>
        <ul>
        <?php
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $path = $dir . "/" . $file;
                if (is_dir($path)) {
                    echo "<li><a href='?dir=" . urlencode($path) . "'>[DIR] $file</a></li>";
                } else {
                    echo "<li>[FILE] $file 
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='delete_file' value='$path'>
                            <input type='submit' value='Sil'>
                        </form>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='edit_file' value='$path'>
                            <textarea name='file_content'>" . htmlspecialchars(file_get_contents($path)) . "</textarea>
                            <input type='submit' value='Düzenle'>
                        </form>
                    </li>";
                }
            }
        }
        ?>
        </ul>
    </div>

    <div class="section">
        <h3>E-posta Hesabı Oluştur</h3>
        <form method="POST">
            E-posta: <input type="text" name="email"> @<?php echo $domain; ?><br>
            Şifre: <input type="password" name="email_pass"><br>
            <input type="submit" name="create_email" value="Oluştur">
        </form>
        <p><a href="https://<?php echo $_SERVER['HTTP_HOST']; ?>:2096">Webmail’e Git</a></p>
    </div>

    <div class="section">
        <h3>Veritabanı Oluştur</h3>
        <form method="POST">
            Veritabanı Adı: <input type="text" name="db_name"><br>
            Şifre: <input type="password" name="db_pass"><br>
            <input type="submit" name="create_db" value="Oluştur">
        </form>
    </div>

    <div class="section">
        <h3>Subdomain Oluştur</h3>
        <form method="POST">
            Subdomain: <input type="text" name="subdomain"> .<?php echo $domain; ?><br>
            <input type="submit" name="create_subdomain" value="Oluştur">
        </form>
    </div>

    <div class="section">
        <h3>Addon Domain Ekle</h3>
        <form method="POST">
            Alan Adı: <input type="text" name="addon_domain"><br>
            Alt Dizin: <input type="text" name="addon_sub"><br>
            <input type="submit" name="create_addon" value="Ekle">
        </form>
    </div>

    <div class="section">
        <h3>Parked Domain Ekle</h3>
        <form method="POST">
            Alan Adı: <input type="text" name="park_domain"><br>
            <input type="submit" name="park_domain" value="Ekle">
        </form>
    </div>

    <div class="section">
        <h3>FTP Hesabı Oluştur</h3>
        <form method="POST">
            Kullanıcı Adı: <input type="text" name="ftp_user"><br>
            Şifre: <input type="password" name="ftp_pass"><br>
            <input type="submit" name="create_ftp" value="Oluştur">
        </form>
    </div>

    <div class="section">
        <h3>SSH Anahtarı Oluştur</h3>
        <form method="POST">
            <input type="submit" name="generate_ssh" value="Anahtar Oluştur">
        </form>
        <?php if (file_exists("/home/{$_SESSION['cpanel_user']}/.ssh/id_rsa.pub")) {
            echo "<p>Public Key: " . file_get_contents("/home/{$_SESSION['cpanel_user']}/.ssh/id_rsa.pub") . "</p>";
        } ?>
    </div>

    <div class="section">
        <h3>Metrikler</h3>
        <p>Disk Kullanımı: <?php echo round(disk_total_space($user_dir) / 1024 / 1024, 2); ?> MB</p>
        <p>Boş Alan: <?php echo round(disk_free_space($user_dir) / 1024 / 1024, 2); ?> MB</p>
        <p>Hata Logları: <?php echo file_get_contents("/usr/local/appserver/logs/{$_SESSION['cpanel_user']}_error.log") ?: 'Log yok'; ?></p>
    </div>

    <a href="?logout">Çıkış Yap</a>
</body>
</html>

<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
}
?>
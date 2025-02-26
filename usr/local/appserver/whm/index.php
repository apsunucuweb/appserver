<?php
session_start();
require_once '/usr/local/appserver/lib/config.php';
require_once '/usr/local/appserver/lib/user_manager.php';

if (!isset($_SESSION['loggedin'])) {
    if (isset($_POST['login'])) {
        if (UserManager::login($_POST['username'], $_POST['password'])) {
            $security_config = Config::get('/usr/local/appserver/data/security_config.json');
            if ($security_config['2fa'] && $_POST['2fa_code'] !== '123456') {
                echo "<p style='color:red;'>Geçersiz 2FA kodu!</p>";
            } else {
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $_POST['username'];
            }
        } else {
            echo "<p style='color:red;'>Geçersiz giriş!</p>";
        }
    }
    ?>
    <h1>Appserver WHM Giriş</h1>
    <form method="POST">
        Kullanıcı Adı: <input type="text" name="username"><br>
        Şifre: <input type="password" name="password"><br>
        <?php if (Config::get('/usr/local/appserver/data/security_config.json', '2fa')) { ?>
            2FA Kodu: <input type="text" name="2fa_code"><br>
        <?php } ?>
        <input type="submit" name="login" value="Giriş Yap">
    </form>
    <?php
    exit;
}

if (isset($_POST['create_account'])) {
    UserManager::create($_POST['new_username'], $_POST['new_password'], $_POST['domain']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appserver WHM</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .section { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Appserver WHM</h1>
    <p>Hoş geldiniz, <?php echo $_SESSION['username']; ?>!</p>

    <div class="section">
        <h3>Create a New Account</h3>
        <form method="POST">
            Kullanıcı Adı: <input type="text" name="new_username"><br>
            Şifre: <input type="password" name="new_password"><br>
            Alan Adı: <input type="text" name="domain"><br>
            <input type="submit" name="create_account" value="Oluştur">
        </form>
    </div>

    <p><a href="dns_manager.php">DNS Zone Manager</a></p>
    <p><a href="api_shell.php">API Shell</a></p>
    <p><a href="https://<?php echo $_SERVER['HTTP_HOST']; ?>:2083">cPanel’e Git</a></p>
    <p><a href="https://<?php echo $_SERVER['HTTP_HOST']; ?>:2096">Webmail’e Git</a></p>

    <a href="?logout">Çıkış Yap</a>
</body>
</html>

<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
}
?>
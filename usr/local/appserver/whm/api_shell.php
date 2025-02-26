<?php
session_start();
require_once '/usr/local/appserver/lib/config.php';

if (!isset($_SESSION['loggedin'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['api_command'])) {
    $command = $_POST['command'];
    $token = $_POST['token'];
    $api_tokens = Config::get('/usr/local/appserver/data/api_tokens.json');
    $output = isset($api_tokens[$token]) ? shell_exec($command) : "Geçersiz API token!";
}

if (isset($_POST['generate_api_token'])) {
    $token = bin2hex(random_bytes(32));
    $api_tokens = Config::get('/usr/local/appserver/data/api_tokens.json');
    $api_tokens[$token] = ['user' => $_SESSION['username'], 'created' => date('Y-m-d H:i:s')];
    Config::set('/usr/local/appserver/data/api_tokens.json', $api_tokens);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>API Shell</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .section { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
        textarea { width: 100%; height: 100px; }
    </style>
</head>
<body>
    <h1>API Shell</h1>

    <div class="section">
        <h3>Run Command</h3>
        <form method="POST">
            Token: <input type="text" name="token"><br>
            Komut: <input type="text" name="command"><br>
            <input type="submit" name="api_command" value="Çalıştır">
        </form>
        <?php if (isset($output)) echo "<pre>$output</pre>"; ?>
    </div>

    <div class="section">
        <h3>Generate API Token</h3>
        <form method="POST">
            <input type="submit" name="generate_api_token" value="Token Oluştur">
        </form>
        <ul>
            <?php foreach (Config::get('/usr/local/appserver/data/api_tokens.json') as $token => $info) echo "<li>$token - {$info['created']}</li>"; ?>
        </ul>
    </div>

    <a href="index.php">Geri Dön</a>
</body>
</html>
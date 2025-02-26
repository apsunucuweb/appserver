<?php
require_once '/usr/local/appserver/lib/config.php';

class UserManager {
    private static $users_file = '/usr/local/appserver/data/users.json';

    public static function login($username, $password) {
        $users = Config::get(self::$users_file);
        return isset($users[$username]) && password_verify($password, $users[$username]);
    }

    public static function create($username, $password, $domain) {
        $users = Config::get(self::$users_file);
        $users[$username] = password_hash($password, PASSWORD_DEFAULT);
        Config::set(self::$users_file, $users);
        
        exec("useradd -m -s /sbin/nologin $username");
        mkdir("/home/$username/public_html", 0755, true);
        exec("cp -r /usr/local/appserver/skeleton/* /home/$username/public_html/");
        exec("chown -R $username:$username /home/$username");
        
        $vhost = "<VirtualHost *:80>\n    ServerName $domain\n    DocumentRoot /home/$username/public_html\n    CustomLog /usr/local/appserver/logs/$username_access.log combined\n    ErrorLog /usr/local/appserver/logs/$username_error.log\n</VirtualHost>";
        file_put_contents("/etc/httpd/conf.d/$username.conf", $vhost);
        exec("systemctl restart httpd || systemctl restart apache2");
    }
}
<?php
require_once '/usr/local/appserver/lib/config.php';

class DNSUtils {
    private static $dns_file = '/usr/local/appserver/data/dns_zones.json';

    public static function addZone($zone, $ip, $ttl) {
        $dns_zones = Config::get(self::$dns_file);
        $dns_zones[$zone] = ['ip' => $ip, 'ttl' => $ttl, 'records' => []];
        Config::set(self::$dns_file, $dns_zones);

        $zone_conf = "zone \"$zone\" {\n    type master;\n    file \"/var/named/$zone.db\";\n};";
        file_put_contents("/etc/named.conf", $zone_conf, FILE_APPEND);
        $zone_file = "\$TTL $ttl\n@ IN SOA ns1.$zone. admin.$zone. (\n    2025022401\n    3600\n    1800\n    604800\n    86400\n)\n@ IN NS ns1.$zone.\n@ IN A $ip\nns1 IN A $ip";
        file_put_contents("/var/named/$zone.db", $zone_file);
        exec("systemctl restart named || systemctl restart bind9");
    }

    public static function editZone($zone, $record) {
        $dns_zones = Config::get(self::$dns_file);
        $dns_zones[$zone]['records'][] = $record;
        Config::set(self::$dns_file, $dns_zones);

        $zone_file = file_get_contents("/var/named/$zone.db");
        $zone_file .= "\n$record";
        file_put_contents("/var/named/$zone.db", $zone_file);
        exec("systemctl restart named || systemctl restart bind9");
    }

    public static function deleteZone($zone) {
        $dns_zones = Config::get(self::$dns_file);
        unset($dns_zones[$zone]);
        Config::set(self::$dns_file, $dns_zones);

        unlink("/var/named/$zone.db");
        $conf = preg_replace("/zone \"$zone\" {[^}]+};/", '', file_get_contents('/etc/named.conf'));
        file_put_contents('/etc/named.conf', $conf);
        exec("systemctl restart named || systemctl restart bind9");
    }
}
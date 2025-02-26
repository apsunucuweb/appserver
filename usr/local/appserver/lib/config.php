<?php
class Config {
    private static $data = [];

    public static function load($file) {
        if (file_exists($file)) {
            self::$data[$file] = json_decode(file_get_contents($file), true);
        }
    }

    public static function get($file, $key = null) {
        if (!isset(self::$data[$file])) self::load($file);
        return $key ? (self::$data[$file][$key] ?? null) : self::$data[$file];
    }

    public static function set($file, $data) {
        self::$data[$file] = $data;
        file_put_contents($file, json_encode($data));
    }
}
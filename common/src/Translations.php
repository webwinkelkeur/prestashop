<?php
namespace Valued\PrestaShop;

class Translations {
    public static function generate($module, $filename) {
        $base = __DIR__ . '/../translations/' . basename($filename);
        if (!is_file($base)) {
            return;
        }
        $GLOBALS['_MODULE'] = [];
        include_once $base;
        if (!isset($GLOBALS['_MODULE']) || !is_array($GLOBALS['_MODULE'])) {
            return;
        }
        $GLOBALS['_MODULE'] = self::replaceModuleName($GLOBALS['_MODULE'], $module);
    }

    private static function replaceModuleName(array $strings, $module) {
        $result = [];
        foreach ($strings as $k => $v) {
            $k = preg_replace('~^<\{.+?\}~', '<{' . $module . '}', $k, -1, $count);
            if ($count) {
                $result[$k] = $v;
            }
        }
        return $result;
    }
}

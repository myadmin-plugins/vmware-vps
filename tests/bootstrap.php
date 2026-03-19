<?php

declare(strict_types=1);

/**
 * PHPUnit Test Bootstrap for detain/myadmin-vmware-vps
 *
 * Sets up the minimal environment needed for isolated unit testing.
 */

// Try package-level autoloader first, fall back to parent project
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

$loaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    fwrite(STDERR, "Could not find autoload.php. Run composer install.\n");
    exit(1);
}

// Provide stub for myadmin_log if not defined
if (!function_exists('myadmin_log')) {
    function myadmin_log(string $section, string $level, string $message, $line = '', $file = '', string $module = '', int $id = 0): void
    {
        // No-op for testing
    }
}

// Provide stub for get_service_define if not defined
if (!function_exists('get_service_define')) {
    function get_service_define(string $name): int
    {
        $defines = [
            'OPENVZ' => 1, 'KVM_LINUX' => 2, 'KVM_WINDOWS' => 3,
            'XEN_LINUX' => 4, 'XEN_WINDOWS' => 5, 'LXC' => 6,
            'VMWARE' => 7, 'VIRTUOZZO' => 8, 'SSD_OPENVZ' => 9,
            'CLOUD_KVM_LINUX' => 10, 'HYPERV' => 11, 'CLOUD_KVM_WINDOWS' => 12,
            'SSD_VIRTUOZZO' => 13, 'KVMV2' => 14, 'KVMV2_WINDOWS' => 15,
            'KVMV2_STORAGE' => 16,
        ];
        return $defines[$name] ?? 0;
    }
}

// Provide stub for get_module_settings if not defined
if (!function_exists('get_module_settings')) {
    function get_module_settings(string $module): array
    {
        return ['PREFIX' => 'vps', 'TABLE' => 'vps', 'TBLNAME' => 'VPS'];
    }
}

// Provide stub for the gettext translation function
if (!function_exists('_')) {
    function _(string $message): string
    {
        return $message;
    }
}

// Suppress non-critical error output for cleaner test output
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

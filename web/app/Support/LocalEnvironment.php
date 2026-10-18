<?php

namespace App\Support;

use RuntimeException;

final class LocalEnvironment
{
    public static function validate(array $settings): void
    {
        $database = $settings['environment'] === 'testing' ? 'merlin_nova_test' : 'merlin_nova_dev';

        if ($settings['enabled'] !== true
            || ! in_array($settings['environment'], ['local', 'testing'], true)
            || $settings['connection'] !== 'mysql'
            || $settings['host'] !== '127.0.0.1'
            || (int) $settings['port'] !== 3307
            || $settings['database'] !== $database
            || $settings['username'] !== 'nova'
            || ! empty($settings['url'])
            || ! empty($settings['unix_socket'])) {
            throw new RuntimeException('Nova startet nur mit der freigegebenen lokalen MySQL-Konfiguration.');
        }
    }
}

<?php

namespace Tests\Unit;

use App\Support\LocalEnvironment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LocalEnvironmentTest extends TestCase
{
    private function settings(): array
    {
        return ['enabled' => true, 'environment' => 'local', 'connection' => 'mysql', 'host' => '127.0.0.1', 'port' => 3307, 'database' => 'merlin_nova_dev', 'username' => 'nova', 'url' => null, 'socket' => ''];
    }

    public function test_local_database_is_allowed(): void
    {
        LocalEnvironment::validate($this->settings());
        $this->addToAssertionCount(1);
    }

    public function test_separate_test_database_is_allowed(): void
    {
        LocalEnvironment::validate(array_replace($this->settings(), ['environment' => 'testing', 'database' => 'merlin_nova_test']));
        $this->addToAssertionCount(1);
    }

    #[DataProvider('unsafeSettings')]
    public function test_unsafe_settings_are_rejected(array $changes): void
    {
        $this->expectException(RuntimeException::class);
        LocalEnvironment::validate(array_replace($this->settings(), $changes));
    }

    public static function unsafeSettings(): array
    {
        return [
            [['enabled' => false]], [['environment' => 'production']],
            [['host' => 'db.example.com']], [['port' => 3306]],
            [['database' => 'live']], [['username' => 'root']],
            [['connection' => 'sqlite']], [['url' => 'mysql://remote/live']],
            [['unix_socket' => '/tmp/mysql.sock']],
            [['environment' => 'testing']],
        ];
    }
}

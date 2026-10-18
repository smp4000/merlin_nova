<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class MySqlConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_use_only_the_separate_mysql_test_database(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('merlin_nova_test', DB::selectOne('SELECT DATABASE() AS name')->name);
        $this->assertDatabaseCount('users', 0);
    }
}

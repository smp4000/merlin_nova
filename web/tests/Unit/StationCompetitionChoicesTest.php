<?php

namespace Tests\Unit;

use App\Stations\StationCompetitionChoices;
use PHPUnit\Framework\TestCase;

class StationCompetitionChoicesTest extends TestCase
{
    public function test_german_alphabetical_order_and_stable_address_tie_breaker(): void
    {
        $hits = [['name' => 'JET', 'street' => 'Zebraweg', 'source_id' => 'a-jet'], ['name' => 'ARAL', 'source_id' => 'b-aral'],
            ['name' => 'AVIA', 'source_id' => 'c-avia'], ['name' => 'JET', 'street' => 'Ahornweg', 'source_id' => 'd-jet']];
        $sorted = StationCompetitionChoices::sort($hits);
        $this->assertSame(['ARAL', 'AVIA', 'JET', 'JET'], array_column($sorted, 'name'));
        $this->assertSame('d-jet', $sorted[2]['source_id']);
        $this->assertCount(4, $hits);
    }
}

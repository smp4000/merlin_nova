<?php

namespace Tests\Unit;

use App\Stations\StationPriceSnapshot;
use PHPUnit\Framework\TestCase;

class StationPriceSnapshotTest extends TestCase
{
    public function test_structured_closed_price_does_not_invent_observation_time(): void
    {
        $fresh = ['status' => 'ok', 'fetched_at' => '2026-10-19T20:00:00Z', 'data' => ['source_id' => 'example', 'prices' => ['e5' => '1.234'], 'prices_last_known' => ['e5' => true]]];
        $result = StationPriceSnapshot::merge($fresh, null);
        $this->assertTrue($result['data']['prices_last_known']['e5']);
        $this->assertNull($result['data']['prices_observed_at']['e5']);
        $again = $fresh;
        $again['data']['prices']['e5'] = null;
        $this->assertNull(StationPriceSnapshot::merge($again, $result)['data']['prices_observed_at']['e5']);
    }

    public function test_missing_prices_keep_original_observation_and_reopening_replaces_them(): void
    {
        $old = ['status' => 'ok', 'fetched_at' => '2026-10-19T10:00:00Z', 'data' => ['source_id' => 'example', 'prices' => ['e5' => '1.234', 'e10' => '1.200', 'diesel' => null]]];
        $empty = ['status' => 'ok', 'fetched_at' => '2026-10-19T20:00:00Z', 'data' => ['source_id' => 'example', 'prices' => ['e5' => null, 'e10' => null, 'diesel' => null]]];
        $result = StationPriceSnapshot::merge($empty, $old);
        $this->assertSame('1.234', $result['data']['prices']['e5']);
        $this->assertTrue($result['data']['prices_last_known']['e5']);
        $this->assertSame($old['fetched_at'], $result['data']['prices_observed_at']['e5']);
        $this->assertNull($result['data']['prices']['diesel']);
        $empty['fetched_at'] = '2026-10-20T01:00:00Z';
        $again = StationPriceSnapshot::merge($empty, $result);
        $this->assertSame($old['fetched_at'], $again['data']['prices_observed_at']['e5']);
        $empty['data']['prices']['e5'] = '1.345';
        $open = StationPriceSnapshot::merge($empty, $again);
        $this->assertSame('1.345', $open['data']['prices']['e5']);
        $this->assertFalse($open['data']['prices_last_known']['e5']);
        $this->assertSame($empty['fetched_at'], $open['data']['prices_observed_at']['e5']);
        $empty['data']['source_id'] = 'different';
        $different = StationPriceSnapshot::merge($empty, $old);
        $this->assertNull($different['data']['prices']['e10']);
    }
}

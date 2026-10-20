<?php

namespace Tests\Unit;

use App\Stations\Providers\BenzinpreisHtmlParser;
use PHPUnit\Framework\TestCase;

/** Synthetische Quellstruktur ohne HTTP: keine Annahmen über fehlende oder mehrdeutige Zeiten. */
class StationSourceObservationsTest extends TestCase
{
    private function parse(string $extra, string $meta = ''): array
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/benzinpreis/details.html');
        $html = str_replace('</head>', $meta.'</head>', $html);

        return (new BenzinpreisHtmlParser)->details($html.$extra, 'abc-test')['data'];
    }

    public function test_times_include_overnight_but_do_not_guess_equal_or_multiple_intervals(): void
    {
        $data = $this->parse('<p class="e-otimes"><em>24 Stunden</em><span>Montag</span></p><p class="e-otimes"><em>Geschlossen</em><span>Dienstag</span></p><p class="e-otimes"><em>22:00 bis 06:00</em><span>Mittwoch</span></p><p class="e-otimes"><em>00:00 bis 00:00</em><span>Donnerstag</span></p><p class="e-otimes"><em>08:00 bis 12:00, 14:00 bis 18:00</em><span>Freitag</span></p><p class="e-otimes"><em>25:00 bis 28:00</em><span>Samstag</span></p><p class="e-otimes"><em>24 Stunden</em><span>Feiertage</span></p>');
        $this->assertSame(['status' => 'all_day'], $data['opening_hours']['monday']);
        $this->assertSame(['status' => 'closed'], $data['opening_hours']['tuesday']);
        $this->assertSame(['status' => 'interval', 'open' => '22:00', 'close' => '06:00'], $data['opening_hours']['wednesday']);
        $this->assertCount(3, $data['opening_hours']);
        $this->assertCount(4, $data['hours_notes']);
    }

    public function test_duplicate_days_are_not_last_value_wins(): void
    {
        $data = $this->parse(str_repeat('<p class="e-otimes"><em>24 Stunden</em><span>Montag</span></p>', 3));
        $this->assertSame([], $data['opening_hours']);
        $this->assertNotEmpty($data['hours_notes']);
    }

    public function test_explicit_end_of_day_is_normalized_without_guessing(): void
    {
        $data = $this->parse('<p class="e-otimes"><em>06:00 bis 24:00</em><span>Montag</span></p><p class="e-otimes"><em>00:00 bis 24:00</em><span>Dienstag</span></p>');
        $this->assertSame(['status' => 'interval', 'open' => '06:00', 'close' => '00:00'], $data['opening_hours']['monday']);
        $this->assertSame(['status' => 'all_day'], $data['opening_hours']['tuesday']);
    }

    public function test_coordinates_require_unique_bounded_pair_and_keep_zero(): void
    {
        $meta = '<meta property="place:location:latitude" content="0"><meta property="place:location:longitude" content="9.123456789">';
        $this->assertSame(['latitude' => '0', 'longitude' => '9.12345679'], $this->parse('', $meta)['coordinates']);
        $this->assertNull($this->parse('', str_replace('content="0"', 'content="91"', $meta))['coordinates']);
        $this->assertNull($this->parse('', '<meta property="place:location:latitude" content="50">')['coordinates']);
        $this->assertNull($this->parse('', $meta.$meta)['coordinates']);
    }
}

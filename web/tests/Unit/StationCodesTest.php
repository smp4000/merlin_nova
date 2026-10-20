<?php

namespace Tests\Unit;

use App\Stations\StationCodes;
use PHPUnit\Framework\TestCase;

/** Ortsvorschläge sind reine Textregeln, ohne Altprojekt oder Datenbankzugriff. */
class StationCodesTest extends TestCase
{
    public function test_city_proposals_and_normalization(): void
    {
        foreach (['Fulda' => 'FD', 'Petersberg' => 'PB', 'Hünfeld' => 'HF', 'Kassel' => 'KS', 'Ulm' => 'UL', 'Au' => 'AU', 'Bad Hersfeld' => 'BH', 'Sankt Peter-Ording' => 'SPO', '' => 'ST'] as $city => $code) {
            $this->assertSame($code, StationCodes::base($city));
        }
        $this->assertSame('FD-P', StationCodes::normalize(' fd-p '));
    }
}

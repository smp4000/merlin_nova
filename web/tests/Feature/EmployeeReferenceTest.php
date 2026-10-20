<?php

namespace Tests\Feature;

use App\Employees\BankDirectory;
use App\Employees\EmployeeChoices;
use App\Rules\Iban;
use Tests\TestCase;

/** Öffentliche Verzeichnisse und rein formale Rechnung, keine echten Kontodaten. */
class EmployeeReferenceTest extends TestCase
{
    public function test_reference_search_is_bounded_and_expired_data_is_marked(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 19));
        $this->getJson('/reference/banks?q=Frankfurt')->assertOk()->assertJsonPath('current', true);
        $result = app(BankDirectory::class)->search('bank');
        $this->assertLessThanOrEqual(20, count($result['banks']));
        $bank = $result['banks'][0];
        $this->getJson('/reference/banks/'.$bank['blz'])->assertOk()->assertJsonPath('banks.0.blz', $bank['blz']);
        $this->getJson('/reference/banks?q=DE36000000000000000000')->assertUnprocessable();
        $this->getJson('/reference/banks?q=ab')->assertUnprocessable();
        $this->getJson('/reference/banks/00000000')->assertOk()->assertJsonCount(0, 'banks');
        $this->travelTo(now()->setDate(2026, 12, 7));
        $this->getJson('/reference/banks/'.$bank['blz'])->assertOk()->assertJsonPath('current', false);
    }

    public function test_proposal_keeps_zeroes_and_is_only_a_formally_valid_number(): void
    {
        $proposal = Iban::germanProposal('00000000', '1');
        $this->assertSame('000000000000000001', substr($proposal, 4));
        $this->assertTrue(Iban::valid($proposal));
        $this->assertSame($proposal, Iban::germanProposal('00000000', '0000000001'));
        $this->assertNull(Iban::germanProposal('123', '1'));
        $this->assertNull(Iban::germanProposal('00000000', '-1'));
        $this->assertNull(Iban::germanProposal('00000000', '12345678901'));
        $this->assertContains('Deutschland', EmployeeChoices::base('nationality'));
        $this->assertCount(92, EmployeeChoices::base('insurer'));
        $this->assertContains('Promotion', EmployeeChoices::base('vocational'));
    }
}

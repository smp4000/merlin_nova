<?php

namespace App\Employees;

/**
 * Versioniertes öffentliches Bundesbankverzeichnis, keine Kontoabfrage.
 * Nur aktive BLZ-führende Datensätze, keine Ableitung institutsindividueller
 * IBAN-Regeln aus den anderen Prüfziffermethoden. Abgelaufene Daten nicht für
 * automatische Ergänzungen verwenden. IBAN/Kontonummer verlassen den Browser nicht.
 */
final class BankDirectory
{
    public function data(): array
    {
        return json_decode(file_get_contents(config('employee_reference.bank_file', resource_path('reference/banks-de.json'))), true, 512, JSON_THROW_ON_ERROR);
    }

    public function search(string $query): array
    {
        $data = $this->data();
        $query = mb_strtolower(trim($query));
        $banks = array_values(array_filter($data['banks'], fn ($bank) => str_contains(mb_strtolower($bank['name'].' '.$bank['city'].' '.$bank['blz']), $query)));

        return ['banks' => array_slice($banks, 0, 20), 'source' => $data['source'], 'valid_from' => $data['valid_from'], 'valid_until' => $data['valid_until'],
            'current' => now()->toDateString() >= $data['valid_from'] && now()->toDateString() <= $data['valid_until']];
    }

    public function lookup(string $blz): array
    {
        $result = $this->search($blz);
        $result['banks'] = array_values(array_filter($result['banks'], fn ($bank) => $bank['blz'] === $blz));

        return $result;
    }
}

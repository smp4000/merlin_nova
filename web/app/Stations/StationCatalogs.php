<?php

namespace App\Stations;

/**
 * Bewusst freigegebene Stationsauswahlen als eigenständige Nova-Definition.
 * Die Werte wurden aus den zuvor lesend geprüften Übersetzungen/Seedern der
 * Nutzerreferenz fachlich übernommen, nicht durch Ausführen des Altprojekts.
 * Stabile Codes trennen Speicherung und deutsche Anzeige. Kein Zugriff auf
 * fremde Partnerkataloge oder globale Neuanlage durch freie Texteingaben.
 * Vorschlagsfelder bleiben erweiterbar; feste Listen erlauben zusätzlich nur
 * den bereits an derselben autorisierten Station gespeicherten Bestandswert.
 */
final class StationCatalogs
{
    public static function fixed(): array
    {
        return [
            'ownership_type' => [
                'DOFO' => 'DOFO – Händlereigentum, Franchisebetrieb',
                'COFO' => 'COFO – Gesellschaftseigentum, Franchisebetrieb',
                'DODO' => 'DODO – Händlereigentum, Händlerbetrieb',
                'CODO' => 'CODO – Gesellschaftseigentum, Händlerbetrieb',
                'COCO' => 'COCO – Gesellschaftseigentum, Gesellschaftsbetrieb',
            ],
            'academic_title' => ['Dr.' => 'Dr.', 'Prof.' => 'Prof.', 'Prof. Dr.' => 'Prof. Dr.', 'Dipl.-Ing.' => 'Dipl.-Ing.'],
            'shop_type' => ['rewe_to_go' => 'REWE To Go', 'lekkerland' => 'Lekkerland', 'convenience' => 'Convenience', 'bistro' => 'Bistro', 'sonstige' => 'Sonstige'],
            'shop_class' => ['A' => 'Klasse A', 'B' => 'Klasse B', 'C' => 'Klasse C'],
            'assortment_level' => ['basis' => 'Basis', 'standard' => 'Standard', 'premium' => 'Premium'],
            'nielsen_area' => [
                '1' => 'Nielsen I (SH, HH, HB, NI)', '2' => 'Nielsen II (NRW)',
                '3a' => 'Nielsen IIIa (HE, RP, SL)', '3b' => 'Nielsen IIIb (BW)',
                '4' => 'Nielsen IV (BY)', '5' => 'Nielsen V (Berlin)',
                '6' => 'Nielsen VI (MV, BB, ST)', '7' => 'Nielsen VII (TH, SN)',
            ],
        ];
    }

    /** Eigene Werte bleiben ausschließlich im jeweiligen Stationsprofil. */
    public static function suggestions(): array
    {
        return [
            'brand' => ['Aral', 'Shell', 'TotalEnergies', 'ESSO', 'JET', 'ENI (Agip)', 'OMV', 'Orlen (Star)', 'Westfalen', 'HEM', 'OIL!', 'Sprint', 'bft', 'AVIA', 'Q1', 'Raiffeisen', 'Globus', 'classic', 'Calpam', 'Hoyer', 'Nordoel', 'Hessol', 'go', 'Gulf', 'SB Tankstelle', 'team', 'Baywa', 'Roth', 'Supermarkt-Tankstelle', 'Freie Tankstelle'],
            'car_wash_brand' => ['WashTec', 'Christ', 'Otto Christ', 'Kärcher', 'Istobal', 'Ehrle', 'Ceccato'],
            'car_wash_type' => ['Portalwaschanlage', 'Waschstraße', 'SB-Waschbox', 'Kombi (Portal + SB)', 'Bürstenwaschanlage', 'Bürstenlos'],
            'account_type' => ['Geschäftskonto', 'Agenturkonto', 'Lottokonto'],
        ];
    }
}

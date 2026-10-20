<?php

namespace App\Models;

/** Partnerbezogene Vorschlagstexte, keine Personen-/Bankwerte und keine amtlichen Codes. */
class EmployeeChoice extends PartnerOwnedModel
{
    protected $guarded = ['*'];
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GermanValidationTest extends TestCase
{
    public function test_standard_errors_have_german_messages_and_readable_search_names(): void
    {
        foreach (['en', 'de'] as $locale) {
            app()->setLocale($locale);
            $errors = Validator::make([], ['search_city' => 'required', 'search_postal_code' => 'required'])->errors();
            $this->assertSame('Bitte das Feld „Ort für die Suche“ ausfüllen.', $errors->first('search_city'));
            $this->assertSame('Bitte das Feld „Postleitzahl für die Suche“ ausfüllen.', $errors->first('search_postal_code'));
            $errors = Validator::make(['email' => 'ungueltig', 'confirmed' => '0', 'label' => 'abcdef'], ['email' => 'email', 'confirmed' => 'accepted', 'label' => 'max:3'])->errors();
            $this->assertSame('Bitte eine gültige E-Mail-Adresse eingeben.', $errors->first('email'));
            $this->assertSame('Bitte die erforderliche Bestätigung geben.', $errors->first('confirmed'));
            $this->assertSame('Auswahltext darf höchstens 3 Zeichen lang sein.', $errors->first('label'));
        }
    }
}

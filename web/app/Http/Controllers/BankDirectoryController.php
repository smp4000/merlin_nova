<?php

namespace App\Http\Controllers;

use App\Employees\BankDirectory;
use Illuminate\Http\Request;

/** Öffentliche Referenzdaten; keine Personal-/Kontoangaben als Suchparameter zulassen. */
class BankDirectoryController extends Controller
{
    public function search(Request $request, BankDirectory $directory)
    {
        $input = $request->validate(['q' => ['required', 'string', 'min:3', 'max:80']]);
        // Eine vollständige IBAN gehört weder in diese Route noch in Zugriffslogs.
        abort_if(preg_match('/[A-Z]{2}\s*\d{2}[\s\d]{10,}/i', $input['q']), 422);

        return response()->json($directory->search($input['q']))->header('Cache-Control', 'public, max-age=3600');
    }

    public function lookup(string $blz, BankDirectory $directory)
    {
        return response()->json($directory->lookup($blz))->header('Cache-Control', 'public, max-age=3600');
    }
}

<?php

namespace PragmaRX\CountriesLaravel\Package\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use PragmaRX\CountriesLaravel\Package\Facade as CountriesFacade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Flag extends Controller
{
    private function findCountryByCca3(string $cca3): mixed
    {
        if (is_null($country = CountriesFacade::where('cca3', Str::upper($cca3)))) {
            abort(404);
        }

        return $country->first()->hydrateFlag();
    }

    public function download(string $cca3): BinaryFileResponse
    {
        return response()->download($this->getFlagPath($cca3));
    }

    public function file(string $cca3): BinaryFileResponse
    {
        return response()->file($this->getFlagPath($cca3));
    }

    protected function getFlagPath(string $cca3): string
    {
        return $this->findCountryByCca3($cca3)->flag->svg_path;
    }
}

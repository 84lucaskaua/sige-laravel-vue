<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpResponseException;

trait ValidaPin
{
    protected function validarPin(string $pin): void
    {
        if (!Hash::check($pin, Auth::user()->pin)) {
            throw new HttpResponseException(
                response()->json(['message' => 'PIN incorreto.'], 403)
            );
        }
    }
}
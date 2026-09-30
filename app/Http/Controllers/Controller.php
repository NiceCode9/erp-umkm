<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // WAJIB: tanpa trait ini, setiap $this->authorize(...) di controller akan
    // memanggil method yang tidak ada dan melempar Error (HTTP 500).
    // Seluruh policy di app/Policies bergantung pada ability() dari trait ini.
    use AuthorizesRequests;
}

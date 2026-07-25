<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

if (! function_exists('pkl_user')) {
    function pkl_user(): ?Authenticatable
    {
        return Auth::user();
    }
}

if (! function_exists('pkl_user_id')) {
    function pkl_user_id(): ?int
    {
        $id = Auth::id();

        return is_numeric($id) ? (int) $id : null;
    }
}

if (! function_exists('pkl_timezone')) {
    function pkl_timezone(): string
    {
        return (string) config('app.timezone', 'Asia/Jakarta');
    }
}

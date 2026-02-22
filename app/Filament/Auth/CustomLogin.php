<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login;

class CustomLogin extends Login
{
    protected string $view = 'filament.auth.custom-login';
    protected static string $layout = 'filament.auth.layout';
}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $company = \App\Models\Company::first();
        $logoUrl = $company && $company->logo_path ? \Illuminate\Support\Facades\Storage::url($company->logo_path) : asset('images/logo.png');
        $brandName = $company?->name ?? 'Delta Mas Tech';
    @endphp

    <link rel="icon" type="image/png" href="{{ $logoUrl }}">
    <title>{{ $title ?? $brandName }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-gray-900 bg-gray-50">
    {{ $slot }}

    @filamentScripts
</body>

</html>
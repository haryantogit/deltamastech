<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

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

    <style>
        /* Reset any Filament wrapper styles on the login page */
        .fi-simple-layout,
        .fi-simple-main-ctn,
        .fi-simple-main,
        .fi-simple-page {
            min-height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            display: contents !important;
        }
    </style>
</head>

<body class="font-sans antialiased text-gray-900 bg-white h-full m-0 p-0 overflow-hidden">
    {{ $slot }}

    @filamentScripts
</body>

</html>
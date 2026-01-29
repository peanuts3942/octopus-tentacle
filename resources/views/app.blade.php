<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="screen-width" id="screen-width" content="">
        <meta name="robots" content="noindex, nofollow">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <style>
            html, body {
                background-color: #000000 !important;
            }
            :root {
                --main-color: #E85D04;
            }
        </style>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=rubik:400,500,600,700,800,900" rel="stylesheet" />

        <title>{{ $title ?? config('app.name') }}</title>
        <meta name="description" content="{{ $description ?? 'Octopus Tentacle Site' }}">

        @vite(['resources/css/app.css', 'resources/js/app.ts'])

    </head>
    <body>
        @include('components.header')
        <x-sticky-header />

        @yield('content')

        @include('components.footer')

        @include('components.tabbar')

    </body>
</html>

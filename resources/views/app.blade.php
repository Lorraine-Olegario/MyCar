<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-slate-950 antialiased">
        <div id="app" data-app-name="{{ config('app.name') }}"></div>
    </body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- O Vue lê este token e o envia no header X-CSRF-TOKEN em todo POST/PUT/DELETE. --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Cadastro de Pessoas</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#232323] text-gray-200 antialiased">
        <div id="app"></div>
    </body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Dibatasi</title>
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-emerald-950 p-6 text-center text-emerald-50">
    <div class="max-w-md">
        <div class="mx-auto mb-6 grid h-20 w-20 place-items-center rounded-full bg-rose-500/20 text-rose-300">
            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold">Akses Dibatasi</h1>

        <div class="mt-6">
            <x-location-verify label="Buka Akses" :redirect="route('login')" />
        </div>
    </div>
</body>
</html>

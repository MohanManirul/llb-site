<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($faviconUrl = asset('llb_favicon.png') . '?v=' . filemtime(public_path('llb_favicon.png')))
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    <title inertia>{{ config('app.name', 'AinPath') }}</title>
    <meta name="description"
        content="এলএলবি শিক্ষার্থীদের জন্য সেশন ও বিষয়ভিত্তিক সাজেশন, বই ও ক্লাস নোট — বিনামূল্যে।">

    {{-- React Fast Refresh preamble — must come before the Vite tag below,
             or the React plugin throws "can't detect preamble" in dev. --}}
    @viteReactRefresh
    @vite(['resources/js/app.tsx', 'resources/css/app.css'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    <!-- Inertia mounts the React app right here -->
    @inertia
</body>

</html>

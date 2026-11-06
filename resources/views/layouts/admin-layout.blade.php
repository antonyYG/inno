@props([
    'title' => 'Admin Dashboard',
])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    @stack('js')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
    @stack('css')
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">

    @include('layouts.includes.navbar')
    @include('layouts.includes.sidebar')

    <main class="min-h-screen sm:ml-64 pt-16">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6">
            {{ $slot }}
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
    <script src="https://kit.fontawesome.com/414a621bcc.js" crossorigin="anonymous"></script>

    <script>
        // SweetAlert
        @if (session('swal'))
            Swal.fire({
                title: @json(session('swal.title')),
                text: @json(session('swal.message')),
                icon: @json(session('swal.icon')),
                confirmButtonColor: '#020830'
            });
        @endif
    </script>

    @stack('scripts')
</body>
</html>

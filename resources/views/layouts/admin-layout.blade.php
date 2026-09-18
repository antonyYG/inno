@props([
    'title' => 'Admin Dashboard',
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('css')
</head>
<body>

    @include('layouts.includes.navbar')
    @include('layouts.includes.sidebar')

    <div class="min-h-screen flex flex-col items-start sm:ml-64 pt-16 p-4">
        <div class="w-full max-w-7xl">
            {{ $slot }}
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>

</body>

<script>

    // SweetAlert

    @if (session('swal'))
        Swal.fire({
            title: "{{ session('swal.title') }}",
            text: "{{ session('swal.message') }}",
            icon: "success"
        });
    @endif
</script>

@stack('scripts')

</html>

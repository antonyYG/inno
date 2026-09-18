<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'CRUD Agency' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased">

    <div class="flex min-h-screen w-full bg-[#020830]">

        {{-- Panel izquierdo: marca --}}
        <div class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-[#000033] via-[#04103f] to-[#072a47] lg:flex lg:flex-col lg:justify-between">

            {{-- patrón geométrico sutil --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
                style="background-image: radial-gradient(circle at 1px 1px, #ffffff 1px, transparent 0); background-size: 28px 28px;"></div>

            {{-- glow decorativo --}}
            <div class="pointer-events-none absolute -bottom-40 -left-40 h-[520px] w-[520px] rounded-full bg-gradient-to-tr from-[#f2c94c]/20 via-cyan-500/10 to-transparent blur-3xl"></div>
            <div class="pointer-events-none absolute -top-24 right-0 h-72 w-72 rounded-full bg-cyan-400/10 blur-3xl"></div>

            {{-- Logo --}}
            <div class="relative z-10 flex items-center gap-2.5 p-10">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-10 w-10 shrink-0 text-[#f2c94c]">
                    <path d="M12 2 4 5v6c0 5.25 3.4 9.74 8 11 4.6-1.26 8-5.75 8-11V5l-8-3Z" stroke="currentColor" stroke-width="1.5" />
                    <path d="M8 12h8M8 9h8M8 15h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
                <div class="flex flex-col leading-tight">
                    <span class="text-lg font-light uppercase tracking-wide text-[#f2c94c]">CRUD</span>
                    <span class="-mt-1 text-lg font-bold uppercase tracking-wide text-white">Agency</span>
                </div>
            </div>

            {{-- Texto de marca --}}
            <div class="relative z-10 max-w-md p-10 pb-16">
                <h2 class="text-3xl font-semibold leading-tight text-white xl:text-4xl">
                    Gestiona tu operación desde un solo lugar
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-slate-300">
                    Accede a tu panel para administrar proyectos, equipos y clientes de forma segura y eficiente.
                </p>
            </div>
        </div>

        {{-- Panel derecho: formulario --}}
        <div class="flex w-full items-center justify-center bg-[#f7f8fb] px-6 py-12 lg:w-1/2">
            <div class="w-full max-w-sm">

                {{-- Logo visible solo en móvil --}}
                <div class="mb-10 flex items-center justify-center gap-2.5 lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-9 w-9 shrink-0 text-[#020830]">
                        <path d="M12 2 4 5v6c0 5.25 3.4 9.74 8 11 4.6-1.26 8-5.75 8-11V5l-8-3Z" stroke="currentColor" stroke-width="1.5" />
                        <path d="M8 12h8M8 9h8M8 15h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                    <div class="flex flex-col leading-tight">
                        <span class="text-base font-light uppercase tracking-wide text-[#020830]">CRUD</span>
                        <span class="-mt-1 text-base font-bold uppercase tracking-wide text-[#020830]">Agency</span>
                    </div>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>

</body>
</html>

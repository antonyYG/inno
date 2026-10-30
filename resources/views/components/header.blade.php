<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
        {{ $slot }}
    </h1>

    @isset($actions)
        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
            {{ $actions }}
        </div>
    @endisset
</div>

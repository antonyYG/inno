<div class="bg-white shadow-xl rounded-xl m-2 sm:m-4 p-3 sm:p-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-semibold text-heading">
            {{ $slot }}
        </h1>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>

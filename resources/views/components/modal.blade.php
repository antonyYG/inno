@props([
    'title' => '',
])

<div {{ $attributes->merge(['class' => 'fixed inset-0 z-50 flex items-center justify-center bg-black/50']) }}>

    <div @click.outside="open = false"
         class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">

        <h2 class="text-xl font-semibold">
            {{ $title }}
        </h2>

        <div class="mt-4">
            {{ $slot }}
        </div>

        @isset($actions)
            <div class="mt-6 flex justify-end gap-2">
                {{ $actions }}
            </div>
        @endisset
        

    </div>
</div>

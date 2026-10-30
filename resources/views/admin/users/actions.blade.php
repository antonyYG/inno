{{-- resources/views/users/actions.blade.php --}}
<div class="flex items-center gap-4">

    <a href="{{ route('admin.users.edit', $user) }}"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-600 transition-colors hover:bg-indigo-50"
        title="Editar" aria-label="Editar usuario">
        <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
    </a>

    <label class="inline-flex cursor-pointer items-center" title="Activar / desactivar">
        <input type="checkbox" class="peer sr-only js-toggle-status" role="switch" data-id="{{ $user->id }}"
            data-url="{{ route('admin.users.toggle-status', $user) }}"
            aria-label="Activar o desactivar usuario {{ $user->name }}" @checked($user->estado)
            onchange="toggleStatus(this)" />

        <div
            class="relative h-6 w-11 rounded-full bg-black transition-colors
               after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5
               after:rounded-full after:bg-white after:transition-transform
               peer-checked:bg-green-500 peer-checked:after:translate-x-5
               peer-focus-visible:ring-2 peer-focus-visible:ring-green-500 peer-focus-visible:ring-offset-2">
        </div>
    </label>

</div>

@props([
    'type' => 'text',
    'name' => '',
    'placeholder' => '',
])

<input type="{{ $type }}" name="{{ $name }}" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" placeholder="{{ $placeholder }}"/>

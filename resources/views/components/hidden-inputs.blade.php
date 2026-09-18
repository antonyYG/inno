@props(['data' => [], 'prefix' => null])

@foreach ($data as $key => $value)
    @php $name = $prefix ? "{$prefix}[{$key}]" : $key; @endphp
    @if (is_array($value))
        <x-hidden-inputs :data="$value" :prefix="$name" />
    @else
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endif
@endforeach

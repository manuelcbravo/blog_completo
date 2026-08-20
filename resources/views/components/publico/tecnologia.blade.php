@props(['nombre', 'destacada' => false, 'acento' => false])

@php
    $marca = config('iconos.marcas')[$nombre] ?? null;
    $glifo = config('iconos.glifos')[$nombre] ?? null;
@endphp

<span @class(['tecnologia', 'tecnologia--destacada' => $destacada, 'tecnologia--acento' => $acento])>
    @if ($marca)
        <span
            class="tecnologia__marca"
            style="--marca: url('{{ asset('assets/svg/stack/'.$marca.'.svg') }}')"
            aria-hidden="true"
        ></span>
    @elseif ($glifo)
        <i class="bi bi-{{ $glifo }} tecnologia__glifo" aria-hidden="true"></i>
    @endif

    {{ $nombre }}
</span>

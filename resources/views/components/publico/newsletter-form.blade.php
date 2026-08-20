@props(['origen' => 'sitio público', 'compacto' => false])

@if (session('suscripcion'))
    <p class="aviso">{{ session('suscripcion') }}</p>
@else
    <form class="formulario" method="POST" action="{{ route('publico.suscribir') }}">
        @csrf
        <input type="hidden" name="origen" value="{{ $origen }}">

        <label class="trampa" aria-hidden="true">
            Deja este campo vacío
            <input type="text" name="sitio_web" tabindex="-1" autocomplete="off">
        </label>

        <div class="formulario__linea">
            <label class="trampa" for="correo-{{ $origen }}">Tu correo</label>
            <input
                id="correo-{{ $origen }}"
                class="campo"
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="tu@correo.com"
                required
            >
            <button class="boton" type="submit">Suscribirme</button>
        </div>

        @error('email')
            <span class="error">{{ $message }}</span>
        @enderror
    </form>
@endif

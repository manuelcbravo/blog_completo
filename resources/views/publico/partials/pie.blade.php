@php
    $autor = config('blog.sitio.autor');
@endphp

<footer class="pie">
    <span>{{ $marca }}<span style="color: var(--accent);">_</span> — © {{ now()->year }}</span>

    <div class="pie__enlaces">
        <a href="{{ route('feed') }}">rss</a>
        @if ($autor['github'])
            <a href="{{ $autor['github'] }}" rel="me noopener" target="_blank">github</a>
        @endif
        <a href="{{ route('publico.sobre') }}">sobre</a>
        <a href="{{ route('publico.newsletter') }}">newsletter</a>
        <a href="{{ route('publico.privacidad') }}">privacidad</a>
    </div>
</footer>

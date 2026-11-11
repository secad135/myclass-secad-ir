@props(['code', 'language' => 'php', 'label' => null])

@if($code)
    <div class="code-block-wrapper my-2">
        @if($label)
            <div class="code-block-label">{{ $label }}</div>
        @endif
        <pre class="line-numbers language-{{ $language }}"><code class="language-{{ $language }}">{{ $code }}</code></pre>
    </div>
@endif

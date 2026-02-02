<footer class="footer">

    <a href="{{ route('home') }}" class="footer-logo btbf_logo_footer">
        @if(theme('logo_url'))
            <img src="{{ theme('logo_url') }}" alt="{{ theme('site_name', config('app.name')) }}" class="h-6">
        @else
            <span class="text-xl font-bold text-white">{{ theme('site_name', config('app.name')) }}</span>
        @endif
    </a>

    @if(theme('discord_url') || theme('telegram_url') || theme('linktree_url'))
        <div class="footer-social flex items-center justify-center gap-4 my-4">
            @if(theme('discord_url'))
                <a href="{{ theme('discord_url') }}" target="_blank" rel="noopener nofollow" class="footer-social-link" aria-label="Discord">
                    @include('icons.discord')
                </a>
            @endif
            @if(theme('telegram_url'))
                <a href="{{ theme('telegram_url') }}" target="_blank" rel="noopener nofollow" class="footer-social-link" aria-label="Telegram">
                    @include('icons.telegram')
                </a>
            @endif
            @if(theme('linktree_url'))
                <a href="{{ theme('linktree_url') }}" target="_blank" rel="noopener nofollow" class="footer-social-link" aria-label="Linktree">
                    @include('icons.linktree')
                </a>
            @endif
        </div>
    @endif

    @if(theme('contact_email'))
        <p class="footer-contact text-gray-400 text-sm mb-2">
            <a href="mailto:{{ theme('contact_email') }}" class="text-white hover:underline">{{ theme('contact_email') }}</a>
        </p>
    @endif

    <p class="footer-copyright">{{ theme('site_name', config('app.name')) }} &copy; {{ date('Y') }} ALL RIGHTS RESERVED</p>

</footer>
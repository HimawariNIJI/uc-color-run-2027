<x-layouts.admin-auth title="Login">
    <div class="w-full max-w-card">
        <x-admin.flash />

        <section class="rounded-3xl border-[3px] border-primary bg-surface px-6 py-8 shadow-[0_8px_0_0_var(--color-brand-orange)] motion-safe:animate-rise sm:px-9 sm:py-10">
            <div class="flex flex-col items-center text-center">
                <x-logo variant="mark" size="xl" />
                <h1 class="mt-4 font-display text-h3 font-normal uppercase text-primary">UC Color Run 2027</h1>
                <p class="mt-1 text-caption font-semibold uppercase tracking-[0.2em] text-muted">Administration Portal</p>
            </div>

            <hr class="my-6 border-border">

            <h2 class="text-h4 text-primary">Welcome back</h2>
            <p class="mt-1 text-body-sm text-muted">Sign in to manage the event and participant registrations.</p>

            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 flex flex-col gap-4" novalidate data-submit-guard>
                @csrf

                <x-form.field label="Username" for="username">
                    <x-form.input name="username" placeholder="Enter username" autocomplete="username" autocapitalize="none" spellcheck="false" pill required autofocus />
                </x-form.field>

                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between gap-3">
                        <label for="password" class="text-label text-foreground">Password</label>
                        <button
                            type="button"
                            class="-my-1 rounded-md px-1.5 py-1 text-caption font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-primary"
                            aria-controls="password"
                            aria-pressed="false"
                            data-password-toggle
                            data-label-show="Show"
                            data-label-hide="Hide"
                        >Show</button>
                    </div>
                    <x-form.input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" pill required />
                    @error('password')
                        <p id="password-error" class="flex items-start gap-1.5 text-caption font-medium text-error">
                            <x-icon name="circle-alert" class="mt-px size-3.5" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <x-button type="submit" pill block class="mt-2">
                    Sign In
                    <x-icon name="arrow-right" class="size-4.5" />
                </x-button>
            </form>

            <hr class="my-6 border-border">

            <p class="flex items-center justify-center gap-1.5 text-caption text-muted">
                <x-icon name="shield-check" class="size-4" />
                Secure Admin Access
            </p>
        </section>
    </div>
</x-layouts.admin-auth>

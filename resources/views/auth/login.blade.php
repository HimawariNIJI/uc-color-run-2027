@extends('layouts.auth')

@section('title', 'Admin Login - UC Color Run 2027')

@section('content')
    <main class="login-card">

        {{-- Logo and title --}}
        <header class="brand">
            <div class="brand-logo">LOGO</div>
            <h1 class="brand-title">UC COLOR RUN 2027</h1>
            <p class="brand-subtitle">ADMINISTRATION PORTAL</p>
        </header>

        <hr class="divider">

        {{-- Welcome text --}}
        <section class="welcome">
            <h2>Welcome back</h2>
            <p>Sign in to manage the event and participant registrations.</p>
        </section>

        {{-- Login form --}}
        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf

            <div class="field">
                <div class="field-label-row">
                    <label for="username">Username</label>
                </div>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="input @error('username') is-invalid @enderror"
                    placeholder="Enter username"
                    value="{{ old('username') }}"
                    autocomplete="username"
                    required
                    autofocus
                >
                @error('username')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <div class="field-label-row">
                    <label for="password">Password</label>
                    <button type="button" class="toggle-password" id="togglePassword" aria-controls="password">Show</button>
                </div>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="input @error('password') is-invalid @enderror"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
                @error('password')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-submit">Sign In &rarr;</button>
        </form>

        <hr class="divider bottom">

        <p class="footer-note">Secure Admin Access</p>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePassword');

        toggleBtn.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggleBtn.textContent = isHidden ? 'Hide' : 'Show';
        });
    </script>
@endsection
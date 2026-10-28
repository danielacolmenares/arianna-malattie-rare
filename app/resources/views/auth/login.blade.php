@extends('layouts.app', ['title' => 'Accesso riservato · Arianna'])

@section('content')
<section class="auth-page">
    <div class="auth-card">
        <span class="eyebrow">Back office</span>
        <h1 class="auth-title">Accesso riservato</h1>
        <p class="muted">Inserisci le credenziali dell’account dimostrativo autorizzato.</p>

        <form class="auth-form" method="post" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <button class="button" type="submit">Accedi</button>
        </form>

        <p class="auth-note">Il recupero delle credenziali sarà definito nella versione operativa.</p>
        <a class="back auth-back" href="{{ route('pathologies.index') }}">← Torna all’area pubblica</a>
    </div>
</section>
@endsection
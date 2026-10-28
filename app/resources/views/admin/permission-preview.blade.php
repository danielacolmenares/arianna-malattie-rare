@extends('layouts.app', ['title' => $title.' · Arianna'])

@section('content')
<section class="admin-page">
    <div class="container">
        <a class="back" href="{{ route('admin.dashboard') }}">← Torna all’area riservata</a>
        <span class="eyebrow">Permesso verificato</span>
        <h1 class="admin-title">{{ $title }}</h1>
        <p class="lead">{{ $description }}</p>
        <div class="admin-card">
            <span class="status">Accesso consentito</span>
            <p>Il profilo <strong>{{ auth()->user()->role->label() }}</strong> dispone del permesso necessario.</p>
            <p class="muted">La funzione operativa sarà costruita nelle prossime fasi del back office.</p>
        </div>
    </div>
</section>
@endsection

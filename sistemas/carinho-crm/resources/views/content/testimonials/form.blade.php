@extends('layouts.app')

@section('title', isset($testimonial) ? 'Editar Depoimento' : 'Novo Depoimento')

@section('content')
<div class="header">
    <div>
        <h1 class="page-title">{{ isset($testimonial) ? 'Editar Depoimento' : 'Novo Depoimento' }}</h1>
        <p class="text-muted">{{ isset($testimonial) ? 'Atualize as informações do depoimento' : 'Adicione um novo depoimento ao site' }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('content.testimonials') }}" class="btn btn-secondary">Voltar</a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error">
    <ul>
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ isset($testimonial) ? route('content.testimonials.update', $testimonial['id']) : route('content.testimonials.store') }}" method="POST" class="form-container">
    @csrf
    @if(isset($testimonial))
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="name">Nome *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $testimonial['name'] ?? '') }}" required class="form-control">
    </div>

    <div class="form-group">
        <label for="role">Função/Cargo</label>
        <input type="text" id="role" name="role" value="{{ old('role', $testimonial['role'] ?? '') }}" class="form-control" placeholder="Ex: Filha de paciente">
    </div>

    <div class="form-group">
        <label for="content">Depoimento *</label>
        <textarea id="content" name="content" rows="5" required class="form-control">{{ old('content', $testimonial['content'] ?? '') }}</textarea>
    </div>

    <div class="form-group">
        <label for="rating">Avaliação *</label>
        <select id="rating" name="rating" required class="form-control">
            @for($i = 1; $i <= 5; $i++)
            <option value="{{ $i }}" {{ old('rating', $testimonial['rating'] ?? 5) == $i ? 'selected' : '' }}>
                {{ $i }} {{ $i == 1 ? 'estrela' : 'estrelas' }}
            </option>
            @endfor
        </select>
    </div>

    <div class="form-group">
        <label for="avatar_url">URL do Avatar</label>
        <input type="url" id="avatar_url" name="avatar_url" value="{{ old('avatar_url', $testimonial['avatar_url'] ?? '') }}" class="form-control" placeholder="https://...">
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="featured" value="1" {{ old('featured', $testimonial['featured'] ?? false) ? 'checked' : '' }}>
            Destacar no site
        </label>
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="active" value="1" {{ old('active', $testimonial['active'] ?? true) ? 'checked' : '' }}>
            Ativo
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="{{ route('content.testimonials') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection

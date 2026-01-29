@extends('layouts.app')

@section('title', isset($category) ? 'Editar Categoria' : 'Nova Categoria')

@section('content')
<div class="header">
    <div>
        <h1 class="page-title">{{ isset($category) ? 'Editar Categoria' : 'Nova Categoria' }}</h1>
    </div>
    <div class="header-actions">
        <a href="{{ route('content.faq.categories') }}" class="btn btn-secondary">Voltar</a>
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

<form action="{{ isset($category) ? route('content.faq.categories.update', $category['id']) : route('content.faq.categories.store') }}" method="POST" class="form-container">
    @csrf
    @if(isset($category))
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="name">Nome *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $category['name'] ?? '') }}" required class="form-control">
    </div>

    <div class="form-group">
        <label for="slug">Slug *</label>
        <input type="text" id="slug" name="slug" value="{{ old('slug', $category['slug'] ?? '') }}" required pattern="[a-z0-9-]+" class="form-control" placeholder="exemplo-de-slug">
        <small class="form-text">Apenas letras minúsculas, números e hífens</small>
    </div>

    <div class="form-group">
        <label for="sort_order">Ordem</label>
        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $category['sort_order'] ?? 0) }}" min="0" class="form-control">
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="active" value="1" {{ old('active', $category['active'] ?? true) ? 'checked' : '' }}>
            Ativa
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="{{ route('content.faq.categories') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection

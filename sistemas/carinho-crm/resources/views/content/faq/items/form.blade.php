@extends('layouts.app')

@section('title', isset($item) ? 'Editar Item FAQ' : 'Novo Item FAQ')

@section('content')
<div class="header">
    <div>
        <h1 class="page-title">{{ isset($item) ? 'Editar Item FAQ' : 'Novo Item FAQ' }}</h1>
        <p class="text-muted">Categoria: {{ $category['name'] ?? '-' }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('content.faq.items', $category['id']) }}" class="btn btn-secondary">Voltar</a>
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

<form action="{{ isset($item) ? route('content.faq.items.update', [$category['id'], $item['id']]) : route('content.faq.items.store', $category['id']) }}" method="POST" class="form-container">
    @csrf
    @if(isset($item))
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="question">Pergunta *</label>
        <input type="text" id="question" name="question" value="{{ old('question', $item['question'] ?? '') }}" required maxlength="500" class="form-control">
    </div>

    <div class="form-group">
        <label for="answer">Resposta *</label>
        <textarea id="answer" name="answer" rows="8" required class="form-control">{{ old('answer', $item['answer'] ?? '') }}</textarea>
    </div>

    <div class="form-group">
        <label for="sort_order">Ordem</label>
        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $item['sort_order'] ?? 0) }}" min="0" class="form-control">
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="active" value="1" {{ old('active', $item['active'] ?? true) ? 'checked' : '' }}>
            Ativo
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="{{ route('content.faq.items', $category['id']) }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection

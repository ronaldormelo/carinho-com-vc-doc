@extends('layouts.app')

@section('title', isset($page) ? 'Editar Página' : 'Nova Página')

@section('content')
<div class="header">
    <div>
        <h1 class="page-title">{{ isset($page) ? 'Editar Página' : 'Nova Página' }}</h1>
    </div>
    <div class="header-actions">
        <a href="{{ route('content.pages') }}" class="btn btn-secondary">Voltar</a>
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

<form action="{{ isset($page) ? route('content.pages.update', $page['id']) : route('content.pages.store') }}" method="POST" class="form-container">
    @csrf
    @if(isset($page))
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="slug">Slug *</label>
        <input type="text" id="slug" name="slug" value="{{ old('slug', $page['slug'] ?? '') }}" required pattern="[a-z0-9-]+" class="form-control" placeholder="exemplo-de-pagina">
        <small class="form-text">Apenas letras minúsculas, números e hífens</small>
    </div>

    <div class="form-group">
        <label for="title">Título *</label>
        <input type="text" id="title" name="title" value="{{ old('title', $page['title'] ?? '') }}" required class="form-control">
    </div>

    <div class="form-group">
        <label for="status_id">Status *</label>
        <select id="status_id" name="status_id" required class="form-control">
            <option value="1" {{ old('status_id', $page['status_id'] ?? 1) == 1 ? 'selected' : '' }}>Rascunho</option>
            <option value="2" {{ old('status_id', $page['status_id'] ?? 1) == 2 ? 'selected' : '' }}>Publicada</option>
            <option value="3" {{ old('status_id', $page['status_id'] ?? 1) == 3 ? 'selected' : '' }}>Arquivada</option>
        </select>
    </div>

    <div class="form-group">
        <label for="seo_title">Título SEO</label>
        <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $page['seo_title'] ?? '') }}" class="form-control">
    </div>

    <div class="form-group">
        <label for="seo_description">Descrição SEO</label>
        <textarea id="seo_description" name="seo_description" rows="3" maxlength="512" class="form-control">{{ old('seo_description', $page['seo_description'] ?? '') }}</textarea>
    </div>

    <div class="form-group">
        <label for="seo_keywords">Palavras-chave SEO</label>
        <input type="text" id="seo_keywords" name="seo_keywords" value="{{ old('seo_keywords', $page['seo_keywords'] ?? '') }}" class="form-control" placeholder="palavra1, palavra2, palavra3">
    </div>

    <div class="form-group">
        <label for="published_at">Data de Publicação</label>
        <input type="datetime-local" id="published_at" name="published_at" value="{{ old('published_at', isset($page['published_at']) ? \Carbon\Carbon::parse($page['published_at'])->format('Y-m-d\TH:i') : '') }}" class="form-control">
    </div>

    <div class="form-group">
        <label for="content_json">Conteúdo (JSON) *</label>
        <textarea id="content_json" name="content_json" rows="10" required class="form-control" style="font-family: monospace;">{{ old('content_json', isset($page['content_json']) ? json_encode($page['content_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}') }}</textarea>
        <small class="form-text">Formato JSON válido</small>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="{{ route('content.pages') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('content_json').addEventListener('input', function() {
    try {
        JSON.parse(this.value);
        this.style.borderColor = '';
    } catch(e) {
        this.style.borderColor = '#dc3545';
    }
});
</script>
@endpush
@endsection

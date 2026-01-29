@extends('layouts.app')

@section('title', 'Categorias de FAQ')

@section('content')
<div class="header">
    <div>
        <h1 class="page-title">Categorias de FAQ</h1>
        <p class="text-muted">Gerencie as categorias de perguntas frequentes</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('content.faq.categories.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Nova Categoria
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Slug</th>
                <th>Ordem</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
            <tr>
                <td>{{ $category['name'] ?? '-' }}</td>
                <td><code>{{ $category['slug'] ?? '-' }}</code></td>
                <td>{{ $category['sort_order'] ?? 0 }}</td>
                <td>
                    @if($category['active'] ?? false)
                        <span class="badge badge-success">Ativa</span>
                    @else
                        <span class="badge badge-secondary">Inativa</span>
                    @endif
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="{{ route('content.faq.items', $category['id']) }}" class="btn btn-sm btn-primary">Ver Itens</a>
                        <a href="{{ route('content.faq.categories.edit', $category['id']) }}" class="btn btn-sm btn-secondary">Editar</a>
                        <form action="{{ route('content.faq.categories.destroy', $category['id']) }}" method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza? Todos os itens desta categoria serão excluídos.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted">Nenhuma categoria cadastrada</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Itens de FAQ')

@section('content')
<div class="header">
    <div>
        <h1 class="page-title">Itens de FAQ - {{ $category['name'] ?? 'Categoria' }}</h1>
        <p class="text-muted">Gerencie as perguntas e respostas desta categoria</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('content.faq.categories') }}" class="btn btn-secondary">Voltar</a>
        <a href="{{ route('content.faq.items.create', $category['id']) }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Novo Item
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
                <th>Pergunta</th>
                <th>Ordem</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>{{ Str::limit($item['question'] ?? '-', 80) }}</td>
                <td>{{ $item['sort_order'] ?? 0 }}</td>
                <td>
                    @if($item['active'] ?? false)
                        <span class="badge badge-success">Ativo</span>
                    @else
                        <span class="badge badge-secondary">Inativo</span>
                    @endif
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="{{ route('content.faq.items.edit', [$category['id'], $item['id']]) }}" class="btn btn-sm btn-secondary">Editar</a>
                        <form action="{{ route('content.faq.items.destroy', [$category['id'], $item['id']]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este item?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center text-muted">Nenhum item cadastrado nesta categoria</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

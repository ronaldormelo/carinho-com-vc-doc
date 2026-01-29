@extends('layouts.app')

@section('title', 'Páginas')

@section('content')
<div class="header">
    <div>
        <h1 class="page-title">Páginas do Site</h1>
        <p class="text-muted">Gerencie as páginas dinâmicas do site</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('content.pages.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Nova Página
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
                <th>Título</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Publicada em</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pages as $page)
            <tr>
                <td>{{ $page['title'] ?? '-' }}</td>
                <td><code>{{ $page['slug'] ?? '-' }}</code></td>
                <td>
                    @if(($page['status_id'] ?? 0) == 2)
                        <span class="badge badge-success">Publicada</span>
                    @elseif(($page['status_id'] ?? 0) == 1)
                        <span class="badge badge-warning">Rascunho</span>
                    @else
                        <span class="badge badge-secondary">Arquivada</span>
                    @endif
                </td>
                <td>{{ isset($page['published_at']) ? \Carbon\Carbon::parse($page['published_at'])->format('d/m/Y H:i') : '-' }}</td>
                <td>
                    <div class="action-buttons">
                        <a href="{{ route('content.pages.edit', $page['id']) }}" class="btn btn-sm btn-secondary">Editar</a>
                        <form action="{{ route('content.pages.destroy', $page['id']) }}" method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta página?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted">Nenhuma página cadastrada</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

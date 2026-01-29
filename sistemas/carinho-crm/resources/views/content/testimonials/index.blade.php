@extends('layouts.app')

@section('title', 'Depoimentos')

@section('content')
<div class="header">
    <div>
        <h1 class="page-title">Depoimentos</h1>
        <p class="text-muted">Gerencie os depoimentos exibidos no site</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('content.testimonials.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Novo Depoimento
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-error">
    {{ session('error') }}
</div>
@endif

<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Função</th>
                <th>Avaliação</th>
                <th>Status</th>
                <th>Destaque</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($testimonials as $testimonial)
            <tr>
                <td>{{ $testimonial['name'] ?? '-' }}</td>
                <td>{{ $testimonial['role'] ?? '-' }}</td>
                <td>
                    @for($i = 0; $i < ($testimonial['rating'] ?? 5); $i++)
                        ★
                    @endfor
                </td>
                <td>
                    @if($testimonial['active'] ?? false)
                        <span class="badge badge-success">Ativo</span>
                    @else
                        <span class="badge badge-secondary">Inativo</span>
                    @endif
                </td>
                <td>
                    @if($testimonial['featured'] ?? false)
                        <span class="badge badge-primary">Sim</span>
                    @else
                        <span class="badge badge-secondary">Não</span>
                    @endif
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="{{ route('content.testimonials.edit', $testimonial['id']) }}" class="btn btn-sm btn-secondary">Editar</a>
                        <form action="{{ route('content.testimonials.destroy', $testimonial['id']) }}" method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este depoimento?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted">Nenhum depoimento cadastrado</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

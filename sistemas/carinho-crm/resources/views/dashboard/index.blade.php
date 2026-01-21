@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="text-muted">Visão geral do CRM Carinho com Você</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-secondary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Atualizar
        </button>
        <button class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Novo Lead
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value" id="stat-leads-today">-</div>
        <div class="stat-label">Leads Hoje</div>
        <div class="stat-change positive">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
            </svg>
            <span id="stat-leads-change">+0%</span> vs semana passada
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value" id="stat-pipeline">-</div>
        <div class="stat-label">Leads no Pipeline</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value" id="stat-conversion">-</div>
        <div class="stat-label">Taxa de Conversão</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value" id="stat-contracts">-</div>
        <div class="stat-label">Contratos Ativos</div>
    </div>
</div>

<!-- Main Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6);">
    <!-- Recent Leads -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Leads Recentes</h2>
            <a href="{{ route('leads') }}" class="btn btn-outline btn-sm">Ver todos</a>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Cidade</th>
                        <th>Urgência</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody id="recent-leads-table">
                    <tr>
                        <td colspan="5" class="text-center text-muted">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Tasks & Alerts -->
    <div>
        <!-- Tasks -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Tarefas Pendentes</h2>
                <span class="badge badge-warning" id="tasks-count">0</span>
            </div>
            
            <div id="pending-tasks">
                <p class="text-muted text-center">Carregando...</p>
            </div>
        </div>
        
        <!-- Contracts Expiring -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Contratos Expirando</h2>
            </div>
            
            <div id="expiring-contracts">
                <p class="text-muted text-center">Carregando...</p>
            </div>
        </div>
    </div>
</div>

<!-- Pipeline Summary -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Resumo do Pipeline</h2>
        <a href="{{ route('pipeline') }}" class="btn btn-outline btn-sm">Ver Pipeline</a>
    </div>
    
    <div class="pipeline-board" id="pipeline-summary">
        <p class="text-muted text-center" style="width: 100%;">Carregando...</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Dashboard data loading
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

async function loadDashboardData() {
    try {
        const response = await fetch('/api/v1/reports/dashboard', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        
        if (!response.ok) throw new Error('Erro ao carregar dados');
        
        const result = await response.json();
        const data = result.data;
        
        // Update stats
        document.getElementById('stat-leads-today').textContent = data.leads?.today || 0;
        document.getElementById('stat-pipeline').textContent = data.leads?.in_pipeline || 0;
        document.getElementById('stat-conversion').textContent = (data.leads?.conversion_rate || 0) + '%';
        document.getElementById('stat-contracts').textContent = data.contracts?.active || 0;
        
        // Update recent leads table
        updateRecentLeadsTable(data.recent_leads || []);
        
        // Update tasks
        updateTasks(data.tasks || {});
        
        // Update expiring contracts
        updateExpiringContracts(data.expiring_contracts || []);
        
    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
    }
}

function updateRecentLeadsTable(leads) {
    const tbody = document.getElementById('recent-leads-table');
    
    if (leads.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhum lead recente</td></tr>';
        return;
    }
    
    tbody.innerHTML = leads.map(lead => `
        <tr>
            <td>
                <a href="/leads/${lead.id}" style="font-weight: 500;">${lead.name}</a>
            </td>
            <td>${lead.city}</td>
            <td><span class="badge badge-${getUrgencyBadge(lead.urgency?.code)}">${lead.urgency?.label || '-'}</span></td>
            <td><span class="badge status-${lead.status?.code}">${lead.status?.label || '-'}</span></td>
            <td class="text-muted">${formatDate(lead.created_at)}</td>
        </tr>
    `).join('');
}

function updateTasks(tasks) {
    const container = document.getElementById('pending-tasks');
    const count = document.getElementById('tasks-count');
    
    count.textContent = tasks.total_open || 0;
    
    if (!tasks.total_open) {
        container.innerHTML = '<p class="text-muted text-center">Nenhuma tarefa pendente</p>';
        return;
    }
    
    container.innerHTML = `
        <div style="display: flex; flex-direction: column; gap: var(--space-3);">
            <div style="display: flex; justify-content: space-between;">
                <span>Atrasadas</span>
                <span class="badge badge-danger">${tasks.total_overdue || 0}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Para hoje</span>
                <span class="badge badge-warning">${tasks.due_today || 0}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Não atribuídas</span>
                <span class="badge badge-gray">${tasks.unassigned || 0}</span>
            </div>
        </div>
    `;
}

function updateExpiringContracts(contracts) {
    const container = document.getElementById('expiring-contracts');
    
    if (contracts.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">Nenhum contrato expirando</p>';
        return;
    }
    
    container.innerHTML = contracts.map(contract => `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-2) 0; border-bottom: 1px solid var(--color-gray-100);">
            <span>${contract.client?.lead?.name || 'Cliente'}</span>
            <span class="text-muted">${formatDate(contract.end_date)}</span>
        </div>
    `).join('');
}

function getUrgencyBadge(code) {
    switch(code) {
        case 'hoje': return 'danger';
        case 'semana': return 'warning';
        default: return 'gray';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
}
</script>
@endpush

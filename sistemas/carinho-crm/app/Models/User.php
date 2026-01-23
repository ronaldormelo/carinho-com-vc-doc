<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relacionamentos
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    // Métodos de negócio
    public function getOpenTasksCount(): int
    {
        return $this->assignedTasks()
            ->where('status_id', Domain\DomainTaskStatus::OPEN)
            ->count();
    }

    public function getOverdueTasksCount(): int
    {
        return $this->assignedTasks()
            ->where('status_id', Domain\DomainTaskStatus::OPEN)
            ->where('due_at', '<', now())
            ->count();
    }

    public function canManageLeads(): bool
    {
        return $this->hasPermissionTo('manage leads');
    }

    public function canManageClients(): bool
    {
        return $this->hasPermissionTo('manage clients');
    }

    public function canManageContracts(): bool
    {
        return $this->hasPermissionTo('manage contracts');
    }

    public function canViewReports(): bool
    {
        return $this->hasPermissionTo('view reports');
    }

    public function canExportData(): bool
    {
        return $this->hasPermissionTo('export data');
    }
}

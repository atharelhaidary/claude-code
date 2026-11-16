<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_DISPATCHER = 'dispatcher';

    public const ROLE_TECHNICIAN = 'technician';

    protected $fillable = ['name', 'email', 'password', 'role', 'phone'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isDispatcher(): bool
    {
        return $this->role === self::ROLE_DISPATCHER;
    }

    public function isTechnician(): bool
    {
        return $this->role === self::ROLE_TECHNICIAN;
    }

    /**
     * Admins and dispatchers run the office side of the system.
     */
    public function isOffice(): bool
    {
        return $this->isAdmin() || $this->isDispatcher();
    }

    public function assignedRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'technician_id');
    }
}

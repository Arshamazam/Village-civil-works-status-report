<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'designation',
        'is_active',
        'last_login_at',
        'force_password_change',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'force_password_change' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function tehsils(): BelongsToMany
    {
        return $this->belongsToMany(Tehsil::class, 'user_tehsil');
    }

    public function dailyReturns(): HasMany
    {
        return $this->hasMany(DailyReturn::class, 'submitted_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'login_token',
        'token_expires_at',
        'magic_link_requested_at',
        'magic_link_revoked_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'login_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'token_expires_at' => 'datetime',
            'magic_link_requested_at' => 'datetime',
            'magic_link_revoked_at' => 'datetime',
        ];
    }

    /**
     * Get login activities for this user.
     */
    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }
}
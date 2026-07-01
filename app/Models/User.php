<?php

namespace App\Models;

use App\Enums\MarketplaceStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'password',
        'role',
        'is_active',
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
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if ($user->role === UserRole::User) {
                $user->customerProfile()->create([
                    'marketplace_status' => MarketplaceStatus::Active,
                ]);
            }
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isEditor(): bool
    {
        return $this->isInternalUser();
    }

    public function isInternalUser(): bool
    {
        return $this->role?->isInternal() === true;
    }

    public function isLojista(): bool
    {
        return $this->role === UserRole::Lojista;
    }

    public function isCliente(): bool
    {
        return $this->role === UserRole::User;
    }

    public function isMarketplaceActive(): bool
    {
        // Sem perfil de cliente significa que não foi bloqueado ainda — pode comprar
        return $this->customerProfile?->marketplace_status !== MarketplaceStatus::Inactive;
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'uploaded_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function expositor(): HasOne
    {
        return $this->hasOne(Expositor::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function feedLikes(): HasMany
    {
        return $this->hasMany(FeedLike::class);
    }

    public function feedComments(): HasMany
    {
        return $this->hasMany(FeedComment::class);
    }

    public function feedReports(): HasMany
    {
        return $this->hasMany(FeedReport::class);
    }
}

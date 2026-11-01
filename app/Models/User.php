<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Role sengaja tidak fillable: role hanya diberikan lewat assignment properti
 * di seeder tepercaya, tidak pernah berasal dari input formulir.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_PELAPOR = 'pelapor';

    public const ROLE_PEMANTAU = 'pemantau';

    public const ROLE_KOORDINATOR = 'koordinator';

    public const ROLE_LABELS = [
        self::ROLE_PELAPOR => 'Pelapor',
        self::ROLE_PEMANTAU => 'Pemantau Komunitas',
        self::ROLE_KOORDINATOR => 'Koordinator Komunitas',
    ];

    protected $attributes = [
        'role' => self::ROLE_PELAPOR,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function isPelapor(): bool
    {
        return $this->role === self::ROLE_PELAPOR;
    }

    public function isPemantau(): bool
    {
        return $this->role === self::ROLE_PEMANTAU;
    }

    public function isKoordinator(): bool
    {
        return $this->role === self::ROLE_KOORDINATOR;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }
}

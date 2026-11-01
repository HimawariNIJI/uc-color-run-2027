<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Search accounts by name or email.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereLike('name', "%{$term}%")
            ->orWhereLike('email', "%{$term}%"));
    }

    /**
     * Most recent sign-ins first; accounts that never signed in go last.
     */
    #[Scope]
    protected function latestLogin(Builder $query): void
    {
        $query->orderByRaw('last_login_at is null')->latest('last_login_at')->latest('id');
    }

    /**
     * How the participant signs in, shown in the admin console.
     */
    public function authenticationMethod(): string
    {
        return $this->google_id ? 'Google Sign-In' : 'Local Dev Sign-In';
    }

    /**
     * The race registration owned by the user (one per account).
     *
     * @return HasOne<Registration, $this>
     */
    public function registration(): HasOne
    {
        return $this->hasOne(Registration::class);
    }
}

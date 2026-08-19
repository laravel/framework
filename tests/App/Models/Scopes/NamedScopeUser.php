<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Tests\App\Models\Relationships\GenericUser as User;

class NamedScopeUser extends User
{
    protected $table = 'named_scope_users';

    /** {@inheritdoc} */
    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    #[Scope]
    protected function verified(Builder $builder, bool $email = true)
    {
        return $builder->when(
            $email === true,
            fn ($query) => $query->whereNotNull('email_verified_at'),
            fn ($query) => $query->whereNull('email_verified_at'),
        );
    }

    #[Scope]
    protected function verifiedWithoutReturn(Builder $builder, bool $email = true)
    {
        $this->verified($builder, $email);
    }

    public function scopeVerifiedUser(Builder $builder, bool $email = true)
    {
        return $builder->when(
            $email === true,
            fn ($query) => $query->whereNotNull('email_verified_at'),
            fn ($query) => $query->whereNull('email_verified_at'),
        );
    }
}

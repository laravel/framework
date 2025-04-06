<?php

namespace Illuminate\Tests\Integration\Database\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Scope as NamedScope;
use Illuminate\Database\Eloquent\Builder;

class NamedScopeUser extends User
{
    /** {@inheritdoc} */
    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    #[NamedScope]
    protected function verified(Builder $builder, bool $email = true)
    {
        return $builder->when(
            $email === true,
            fn ($query) => $query->whereNotNull('email_verified_at'),
            fn ($query) => $query->whereNull('email_verified_at'),
        );
    }

    #[NamedScope]
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

<?php

namespace Illuminate\Contracts\Auth\Providers;

use Illuminate\Contracts\Auth\Identity\Identifiable;

/**
 * @template-covariant TUser of Identifiable
 *
 * @extends BasicUserProvider<TUser>
 * @extends RecallerUserProvider<TUser>
 * @extends CredentialsUserProvider<TUser>
 */
interface StatefulUserProvider extends BasicUserProvider, RecallerUserProvider, CredentialsUserProvider
{
}

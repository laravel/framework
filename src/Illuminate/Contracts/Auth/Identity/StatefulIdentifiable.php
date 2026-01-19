<?php

namespace Illuminate\Contracts\Auth\Identity;

interface StatefulIdentifiable extends Identifiable, Rememberable, HasPassword
{
}

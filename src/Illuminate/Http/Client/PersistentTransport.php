<?php

namespace Illuminate\Http\Client;

enum PersistentTransport: string
{
    case None = 'none';
    case Preferred = 'preferred';
    case Required = 'required';
}

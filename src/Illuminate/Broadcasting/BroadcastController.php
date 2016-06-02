<?php

namespace Illuminate\Broadcasting;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Broadcast;

class BroadcastController extends Controller
{
    /**
     * Authenticate the request for channel access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function authenticate(Request $request)
    {
        if (Str::startsWith($request->channel_name, 'presence-') && ! $request->user()) {
            abort(403);
        }

        return Broadcast::check($request);
    }

    /**
     * Store the socket ID for the current user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function rememberSocket(Request $request)
    {
        return Broadcast::rememberSocket($request);
    }
}

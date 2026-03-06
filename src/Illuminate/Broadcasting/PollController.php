<?php

namespace Illuminate\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PollBroadcaster;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PollController extends Controller
{
    /**
     * Handle a poll request for broadcast events.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'channels' => ['required', 'array'],
            'channels.*' => ['required', 'string'],
            'lastEventId' => ['nullable', 'string'],
        ]);

        $broadcaster = Broadcast::driver();

        if (! $broadcaster instanceof PollBroadcaster) {
            throw new NotFoundHttpException;
        }

        $authorizedChannels = [];

        foreach ($request->input('channels') as $channel) {
            try {
                $authRequest = Request::create('/', 'GET', ['channel_name' => $channel]);
                $authRequest->setUserResolver($request->getUserResolver());

                $broadcaster->auth($authRequest);
                $authorizedChannels[] = $channel;
            } catch (AccessDeniedHttpException) {
                continue;
            }
        }

        $result = $broadcaster->getEvents($authorizedChannels, $request->input('lastEventId'));

        $socketId = $request->header('X-Socket-ID');

        if ($socketId) {
            $result['events'] = array_values(array_filter(
                $result['events'],
                fn ($event) => ($event['socket'] ?? null) !== $socketId
            ));
        }

        $presence = [];

        foreach ($authorizedChannels as $channel) {
            if (str_starts_with($channel, 'presence-')) {
                $user = $request->user();

                if ($user) {
                    $broadcastIdentifier = method_exists($user, 'getAuthIdentifierForBroadcasting')
                        ? $user->getAuthIdentifierForBroadcasting()
                        : $user->getAuthIdentifier();

                    $presence[$channel] = [
                        'members' => $broadcaster->updatePresence(
                            $channel, $broadcastIdentifier, []
                        ),
                    ];
                }
            }
        }

        return response()->json(array_filter([
            'events' => $result['events'],
            'lastEventId' => $result['lastEventId'],
            'presence' => $presence ?: null,
        ]));
    }
}

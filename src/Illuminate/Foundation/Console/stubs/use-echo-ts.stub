import { useEffect, useRef } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Define types for Echo channels
interface Channel {
  listen(event: string, callback: (payload: any) => void): Channel;
  stopListening(event: string, callback?: (payload: any) => void): Channel;
}

interface EchoInstance extends Echo<any> {
  channel(channel: string): Channel;
  private(channel: string): Channel;
  leaveChannel(channel: string): void;
}

interface ChannelData {
  count: number;
  channel: Channel;
}

interface Channels {
  [channelName: string]: ChannelData;
}

// Create a singleton Echo instance
let echoInstance: EchoInstance | null = null;

// Initialize Echo only once
const getEchoInstance = (): EchoInstance => {
  if (!echoInstance) {
    // Temporarily add Pusher to window object for Echo initialization
    // This is a compromise - we're still avoiding permanent global namespace pollution
    // by only adding it temporarily during initialization
    const originalPusher = (window as any).Pusher;
    (window as any).Pusher = Pusher;
    
    // Configure Echo with Reverb
    echoInstance = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
      wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
    }) as EchoInstance;
    
    // Restore the original Pusher value to avoid side effects
    if (originalPusher) {
      (window as any).Pusher = originalPusher;
    } else {
      delete (window as any).Pusher;
    }
  }
  return echoInstance;
};

// Keep track of all active channels
const channels: Channels = {};

// Export Echo instance for direct access if needed
export const echo = getEchoInstance();

// Helper functions to interact with Echo
export const subscribeToChannel = (channelName: string, isPrivate = false): Channel => {
  return isPrivate ? echo.private(channelName) : echo.channel(channelName);
};

export const leaveChannel = (channelName: string): void => {
  echo.leaveChannel(channelName);
};

// The main hook for using Echo in React components
export default function useEcho(
  channel: string, 
  event: string | string[], 
  callback: (payload: any) => void, 
  dependencies = [], 
  visibility: 'private' | 'public' = 'private'
) {
  const eventRef = useRef(callback);

  useEffect(() => {
    // Always use the latest callback
    eventRef.current = callback;

    const channelName = visibility === 'public' ? channel : `${visibility}-${channel}`;
    const isPrivate = visibility === 'private';

    // Reuse existing channel subscription or create a new one
    if (!channels[channelName]) {
      channels[channelName] = {
        count: 1,
        channel: subscribeToChannel(channel, isPrivate),
      };
    } else {
      channels[channelName].count += 1;
    }

    const subscription = channels[channelName].channel;

    const listener = (payload: any) => {
      eventRef.current(payload);
    };

    const events = Array.isArray(event) ? event : [event];

    // Subscribe to all events
    events.forEach((e) => {
      subscription.listen(e, listener);
    });

    // Cleanup function
    return () => {
      events.forEach((e) => {
        subscription.stopListening(e, listener);
      });
      
      channels[channelName].count -= 1;
      if (channels[channelName].count === 0) {
        leaveChannel(channelName);
        delete channels[channelName];
      }
    };
  }, [...dependencies]); // eslint-disable-line
}
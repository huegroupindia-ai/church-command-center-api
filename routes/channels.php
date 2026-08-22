<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application uses. Channels provide a way to subscribe to events from
| your Laravel backend and receive them in real-time on the client.
|
*/

// Public channel for dashboard real-time updates
// All authenticated users can listen on this channel
Broadcast::channel('dashboard.updates', function ($user) {
    return true; // Everyone authenticated can listen
});

// Private channel for user-specific updates
Broadcast::channel('private.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Private channel for volunteer check-in updates
Broadcast::channel('volunteer.checkins', function ($user) {
    return true;
});

// Private channel for equipment alerts
Broadcast::channel('equipment.alerts', function ($user) {
    return true;
});

// Private channel for incident updates
Broadcast::channel('incidents', function ($user) {
    return true;
});

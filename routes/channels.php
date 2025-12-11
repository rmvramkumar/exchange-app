<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for order updates (Echo adds 'private-' prefix automatically)
Broadcast::channel('user.{id}', function ($user, $id) {
    // Allow access if user is authenticated and the ID matches
    if ($user && (int) $user->id === (int) $id) {
        return true;
    }
    return false;
});

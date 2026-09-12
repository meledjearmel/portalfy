<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Le suivi temps réel d'une commande (orders.{reference}) utilise un canal
// public — voir le commentaire sur HotspotAccountProvisioned::broadcastOn().

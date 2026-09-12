<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Tableau de bord admin (guard "web") : tout compte staff authentifié peut
// suivre l'activité en temps réel, il n'y a pas de rôles/permissions
// granulaires dans ce projet. Le "return true" n'autorise pas un visiteur
// anonyme : PusherBroadcaster::auth() (utilisé aussi par le driver "reverb",
// cf. BroadcastManager::createReverbDriver()) rejette déjà toute demande sur
// un canal "private-*" dont retrieveUser() renvoie null, avant même d'appeler
// cette closure — un guest n'atteint jamais ce code.
Broadcast::channel('admin.dashboard', function () {
    return true;
});

// Le suivi temps réel d'une commande (orders.{reference}) utilise un canal
// public — voir le commentaire sur HotspotAccountProvisioned::broadcastOn().

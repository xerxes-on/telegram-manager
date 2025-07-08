<?php

namespace App\Jobs\Interfaces;

use App\Models\Client;

interface SubscriptionNotifier
{
    public function notify(Client $client, int $daysLeft): void;
}

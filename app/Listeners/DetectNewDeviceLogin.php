<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\LoginSecurityService;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class DetectNewDeviceLogin
{
    public function __construct(
        private readonly LoginSecurityService $security,
        private readonly Request $request,
    ) {
    }

    public function handle(Login $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User) {
            return;
        }

        $this->security->inspect($event->user, $this->request);
    }
}

<?php

namespace App\Listeners;

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
        $this->security->inspect($event->user, $this->request);
    }
}

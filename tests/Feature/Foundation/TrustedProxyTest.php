<?php

namespace Tests\Feature\Foundation;

use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_livewire_uses_forwarded_https_scheme_behind_cloudflare_tunnel(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '172.18.0.1'])
            ->withHeaders([
                'Host' => 'sistemhotel.muhammadrihaz.my.id',
                'X-Forwarded-Host' => 'sistemhotel.muhammadrihaz.my.id',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/login');

        $response
            ->assertOk()
            ->assertSee(
                'data-update-uri="https://sistemhotel.muhammadrihaz.my.id/livewire-',
                escape: false,
            );
    }
}

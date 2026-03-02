<?php

use App\Models\App;
use App\Models\DeferredLink;
use App\Models\Link;
use App\Services\FingerprintService;

function makeApp(): App
{
    return App::create([
        'name' => 'Deferred Test App',
        'api_key' => 'deferred-api-key',
        'bundle_id_ios' => 'com.deferred.app',
        'bundle_id_android' => 'com.deferred.app',
        'app_store_url' => 'https://apps.apple.com/app/deferred',
        'play_store_url' => 'https://play.google.com/store/apps/deferred',
        'uri_scheme' => 'deferredapp://',
    ]);
}

function makeLink(App $app): Link
{
    return Link::create([
        'app_id' => $app->id,
        'short_code' => 'def001',
        'deep_link_uri' => 'deferredapp://products/99',
    ]);
}

test('deferred resolve returns matched true on exact fingerprint', function () {
    $app = makeApp();
    $link = makeLink($app);

    $fp = app(FingerprintService::class)->compute(
        '127.0.0.1',
        'TestAgent/1.0',
        'en-US',
        390,
        844
    );

    DeferredLink::create([
        'link_id' => $link->id,
        'app_id' => $app->id,
        'fingerprint' => $fp,
        'platform' => 'ios',
        'expires_at' => now()->addHours(24),
    ]);

    $this->postJson('/api/deferred/resolve', [
        'user_agent' => 'TestAgent/1.0',
        'platform' => 'ios',
        'language' => 'en-US',
        'screen_width' => 390,
        'screen_height' => 844,
    ], ['X-Api-Key' => 'deferred-api-key', 'REMOTE_ADDR' => '127.0.0.1'])
        ->assertStatus(200)
        ->assertJsonPath('matched', true)
        ->assertJsonPath('deep_link_uri', 'deferredapp://products/99');
});

test('deferred resolve returns matched false when no match', function () {
    makeApp();

    $this->postJson('/api/deferred/resolve', [
        'user_agent' => 'NoMatchAgent/1.0',
        'platform' => 'ios',
        'language' => 'en-US',
        'screen_width' => 390,
        'screen_height' => 844,
    ], ['X-Api-Key' => 'deferred-api-key'])
        ->assertStatus(200)
        ->assertJsonPath('matched', false);
});

test('deferred resolve marks link as resolved', function () {
    $app = makeApp();
    $link = makeLink($app);

    $fp = app(FingerprintService::class)->compute(
        '127.0.0.1',
        'TestAgent/2.0',
        'en',
        0,
        0
    );

    $deferred = DeferredLink::create([
        'link_id' => $link->id,
        'app_id' => $app->id,
        'fingerprint' => $fp,
        'platform' => 'android',
        'expires_at' => now()->addHours(24),
    ]);

    $this->postJson('/api/deferred/resolve', [
        'user_agent' => 'TestAgent/2.0',
        'platform' => 'android',
        'language' => 'en',
        'screen_width' => 0,
        'screen_height' => 0,
    ], ['X-Api-Key' => 'deferred-api-key']);

    $this->assertDatabaseHas('deferred_links', [
        'id' => $deferred->id,
        'resolved' => 1,
    ]);
});

<?php

use App\Models\App;
use App\Models\Link;

beforeEach(function () {
    $this->testApp = App::create([
        'name' => 'Redirect Test App',
        'api_key' => 'redirect-api-key',
        'bundle_id_ios' => 'com.redirect.app',
        'bundle_id_android' => 'com.redirect.app',
        'app_store_url' => 'https://apps.apple.com/app/redirect',
        'play_store_url' => 'https://play.google.com/store/apps/redirect',
        'uri_scheme' => 'redirectapp://',
    ]);
});

test('redirect page loads for valid short code', function () {
    Link::create([
        'app_id' => $this->testApp->id,
        'short_code' => 'redir1',
        'deep_link_uri' => 'redirectapp://home',
    ]);

    $this->get('/redir1')
        ->assertStatus(200)
        ->assertSee('redirectapp://home', false);
});

test('redirect returns 404 for unknown short code', function () {
    $this->get('/unknowncode')
        ->assertStatus(404);
});

test('redirect returns 404 for expired link', function () {
    Link::create([
        'app_id' => $this->testApp->id,
        'short_code' => 'exprd1',
        'deep_link_uri' => 'redirectapp://expired',
        'expires_at' => now()->subHour(),
    ]);

    $this->get('/exprd1')
        ->assertStatus(404);
});

test('redirect records link click', function () {
    Link::create([
        'app_id' => $this->testApp->id,
        'short_code' => 'click1',
        'deep_link_uri' => 'redirectapp://click',
    ]);

    $this->get('/click1');

    $this->assertDatabaseHas('link_clicks', ['app_id' => $this->testApp->id]);
});

test('redirect creates deferred link record', function () {
    Link::create([
        'app_id' => $this->testApp->id,
        'short_code' => 'defer1',
        'deep_link_uri' => 'redirectapp://defer',
    ]);

    $this->get('/defer1');

    $this->assertDatabaseHas('deferred_links', ['app_id' => $this->testApp->id]);
});

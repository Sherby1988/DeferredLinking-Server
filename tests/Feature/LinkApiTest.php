<?php

use App\Models\App;
use App\Models\Link;

function createTestApp(string $apiKey = 'test-api-key-1234'): App
{
    return App::create([
        'name' => 'Test App',
        'api_key' => $apiKey,
        'bundle_id_ios' => 'com.test.app',
        'bundle_id_android' => 'com.test.app',
        'app_store_url' => 'https://apps.apple.com/app/test',
        'play_store_url' => 'https://play.google.com/store/apps/test',
        'uri_scheme' => 'testapp://',
    ]);
}

test('create link requires api key', function () {
    $this->postJson('/api/links', ['deep_link_uri' => 'testapp://home'])
        ->assertStatus(401);
});

test('create link returns short url', function () {
    createTestApp();

    $this->postJson('/api/links', [
        'deep_link_uri' => 'testapp://products/42',
    ], ['X-Api-Key' => 'test-api-key-1234'])
        ->assertStatus(201)
        ->assertJsonStructure(['link', 'short_url'])
        ->assertJsonPath('link.deep_link_uri', 'testapp://products/42');

    $this->assertDatabaseHas('links', ['deep_link_uri' => 'testapp://products/42']);
});

test('list links', function () {
    $app = createTestApp();

    Link::create([
        'app_id' => $app->id,
        'short_code' => 'abc123',
        'deep_link_uri' => 'testapp://home',
    ]);

    $this->getJson('/api/links', ['X-Api-Key' => 'test-api-key-1234'])
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'total']);
});

test('get link by code', function () {
    $app = createTestApp();

    Link::create([
        'app_id' => $app->id,
        'short_code' => 'xyz789',
        'deep_link_uri' => 'testapp://detail/1',
    ]);

    $this->getJson('/api/links/xyz789', ['X-Api-Key' => 'test-api-key-1234'])
        ->assertStatus(200)
        ->assertJsonPath('link.short_code', 'xyz789');
});

test('delete link', function () {
    $app = createTestApp();

    Link::create([
        'app_id' => $app->id,
        'short_code' => 'del001',
        'deep_link_uri' => 'testapp://delete-me',
    ]);

    $this->deleteJson('/api/links/del001', [], ['X-Api-Key' => 'test-api-key-1234'])
        ->assertStatus(200);

    $this->assertDatabaseMissing('links', ['short_code' => 'del001']);
});

test('cannot access another apps link', function () {
    createTestApp('test-api-key-1234');

    $otherApp = App::create([
        'name' => 'Other App',
        'api_key' => 'other-api-key-5678',
        'bundle_id_ios' => 'com.other.app',
        'bundle_id_android' => 'com.other.app',
        'app_store_url' => 'https://apps.apple.com/app/other',
        'play_store_url' => 'https://play.google.com/store/apps/other',
        'uri_scheme' => 'otherapp://',
    ]);

    Link::create([
        'app_id' => $otherApp->id,
        'short_code' => 'other1',
        'deep_link_uri' => 'otherapp://home',
    ]);

    $this->getJson('/api/links/other1', ['X-Api-Key' => 'test-api-key-1234'])
        ->assertStatus(404);
});

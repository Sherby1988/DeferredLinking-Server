<?php

use App\Models\App;

beforeEach(function () {
    config(['deferred_linking.admin_key' => 'test-admin-key']);
});

test('create app requires admin key', function () {
    $this->postJson('/api/apps', [])
        ->assertStatus(401);
});

test('create app with valid data', function () {
    $this->postJson('/api/apps', [
        'name' => 'Test App',
        'bundle_id_ios' => 'com.test.app',
        'bundle_id_android' => 'com.test.app',
        'app_store_url' => 'https://apps.apple.com/app/test',
        'play_store_url' => 'https://play.google.com/store/apps/test',
        'uri_scheme' => 'testapp://',
    ], ['X-Admin-Key' => 'test-admin-key'])
        ->assertStatus(201)
        ->assertJsonPath('app.name', 'Test App')
        ->assertJsonStructure(['app' => ['api_key']]);
});

test('get app by id', function () {
    $app = App::create([
        'name' => 'Test App',
        'api_key' => bin2hex(random_bytes(32)),
        'bundle_id_ios' => 'com.test.app',
        'bundle_id_android' => 'com.test.app',
        'app_store_url' => 'https://apps.apple.com/app/test',
        'play_store_url' => 'https://play.google.com/store/apps/test',
        'uri_scheme' => 'testapp://',
    ]);

    $this->getJson("/api/apps/{$app->id}", ['X-Admin-Key' => 'test-admin-key'])
        ->assertStatus(200)
        ->assertJsonPath('app.name', 'Test App');
});

test('delete app', function () {
    $app = App::create([
        'name' => 'Test App',
        'api_key' => bin2hex(random_bytes(32)),
        'bundle_id_ios' => 'com.test.app',
        'bundle_id_android' => 'com.test.app',
        'app_store_url' => 'https://apps.apple.com/app/test',
        'play_store_url' => 'https://play.google.com/store/apps/test',
        'uri_scheme' => 'testapp://',
    ]);

    $this->deleteJson("/api/apps/{$app->id}", [], ['X-Admin-Key' => 'test-admin-key'])
        ->assertStatus(200);

    $this->assertDatabaseMissing('apps', ['id' => $app->id]);
});

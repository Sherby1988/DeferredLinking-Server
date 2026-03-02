<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bundle_id_ios' => 'required|string|max:255',
            'bundle_id_android' => 'required|string|max:255',
            'app_store_url' => 'required|url|max:2048',
            'play_store_url' => 'required|url|max:2048',
            'custom_domain' => 'nullable|string|max:255|unique:apps,custom_domain',
            'uri_scheme' => 'required|string|max:255',
        ]);

        $apiKey = bin2hex(random_bytes(32));

        $app = App::create(array_merge($validated, ['api_key' => $apiKey]));

        return response()->json([
            'app' => $app->makeVisible('api_key'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $app = App::findOrFail($id);
        return response()->json(['app' => $app]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $app = App::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'bundle_id_ios' => 'sometimes|string|max:255',
            'bundle_id_android' => 'sometimes|string|max:255',
            'app_store_url' => 'sometimes|url|max:2048',
            'play_store_url' => 'sometimes|url|max:2048',
            'custom_domain' => 'nullable|string|max:255|unique:apps,custom_domain,' . $app->id,
            'uri_scheme' => 'sometimes|string|max:255',
        ]);

        $app->update($validated);

        return response()->json(['app' => $app]);
    }

    public function destroy(int $id): JsonResponse
    {
        $app = App::findOrFail($id);
        $app->delete();
        return response()->json(['message' => 'App deleted']);
    }
}

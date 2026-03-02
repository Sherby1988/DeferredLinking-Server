<?php

namespace App\Console\Commands;

use App\Models\App;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateApp extends Command
{
    protected $signature = 'deferred-linking:create-app
                            {--name=           : App display name}
                            {--ios=            : iOS bundle ID (e.g. com.example.app)}
                            {--android=        : Android bundle ID (e.g. com.example.app)}
                            {--app-store=      : App Store URL}
                            {--play-store=     : Play Store URL}
                            {--scheme=         : URI scheme (e.g. myapp://)}
                            {--domain=         : Custom domain (e.g. links.myapp.com, optional)}';

    protected $description = 'Register a new app and generate its API key';

    public function handle(): int
    {
        $this->info('Deferred Linking — Create App');
        $this->line('');

        $data = [
            'name'              => $this->option('name')       ?? $this->ask('App name'),
            'bundle_id_ios'     => $this->option('ios')        ?? $this->ask('iOS bundle ID (e.g. com.example.app)'),
            'bundle_id_android' => $this->option('android')    ?? $this->ask('Android bundle ID (e.g. com.example.app)'),
            'app_store_url'     => $this->option('app-store')  ?? $this->ask('App Store URL'),
            'play_store_url'    => $this->option('play-store') ?? $this->ask('Play Store URL'),
            'uri_scheme'        => $this->option('scheme')     ?? $this->ask('URI scheme (e.g. myapp://)'),
            'custom_domain'     => $this->resolveDomain(),
        ];

        $validator = Validator::make($data, [
            'name'              => 'required|string|max:255',
            'bundle_id_ios'     => 'required|string|max:255',
            'bundle_id_android' => 'required|string|max:255',
            'app_store_url'     => 'required|url|max:2048',
            'play_store_url'    => 'required|url|max:2048',
            'uri_scheme'        => 'required|string|max:255',
            'custom_domain'     => 'nullable|string|max:255|unique:apps,custom_domain',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $apiKey = bin2hex(random_bytes(32));

        $app = App::create(array_merge($validator->validated(), ['api_key' => $apiKey]));

        $this->line('');
        $this->info('App created successfully.');
        $this->line('');

        $this->table(
            ['Field', 'Value'],
            [
                ['ID',               $app->id],
                ['Name',             $app->name],
                ['iOS bundle',       $app->bundle_id_ios],
                ['Android bundle',   $app->bundle_id_android],
                ['URI scheme',       $app->uri_scheme],
                ['Custom domain',    $app->custom_domain ?? '(using default: ' . config('deferred_linking.default_domain') . ')'],
            ]
        );

        $this->line('');
        $this->line('  <fg=yellow;options=bold>API Key (store this — it will not be shown again):</>');
        $this->line('');
        $this->line("  <fg=green;options=bold>  {$apiKey}  </>");
        $this->line('');

        return self::SUCCESS;
    }

    private function resolveDomain(): ?string
    {
        $value = $this->option('domain');
        return ($value !== null && $value !== '') ? $value : null;
    }
}

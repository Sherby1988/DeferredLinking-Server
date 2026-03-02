<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    protected $fillable = [
        'name',
        'api_key',
        'bundle_id_ios',
        'bundle_id_android',
        'app_store_url',
        'play_store_url',
        'custom_domain',
        'uri_scheme',
    ];

    protected $hidden = ['api_key'];

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function linkClicks(): HasMany
    {
        return $this->hasMany(LinkClick::class);
    }

    public function deferredLinks(): HasMany
    {
        return $this->hasMany(DeferredLink::class);
    }
}

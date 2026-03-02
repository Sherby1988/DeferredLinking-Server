<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeferredLink extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'link_id',
        'app_id',
        'fingerprint',
        'platform',
        'resolved',
        'resolved_at',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}

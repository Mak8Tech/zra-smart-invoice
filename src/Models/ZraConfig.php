<?php

namespace Mak8Tech\ZraSmartInvoice\Models;

use Illuminate\Database\Eloquent\Model;

class ZraConfig extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tpin',
        'branch_id',
        'device_serial',
        'api_key',
        'environment',
        'last_initialized_at',
        'last_sync_at',
        'additional_config',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_initialized_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'additional_config' => 'json',
    ];

    /**
     * Get the currently active configuration
     *
     * @return self|null
     */
    public static function getActive()
    {
        return static::latest()->first();
    }

    /**
     * Determine if this device has been initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return !empty($this->api_key) && !is_null($this->last_initialized_at);
    }

    /**
     * Get initialization status with friendly message
     *
     * @return array
     */
    public function getStatus(): array
    {
        if ($this->isInitialized()) {
            return [
                'status' => 'initialized',
                'message' => 'Device successfully initialized',
                'last_initialized' => $this->last_initialized_at->diffForHumans(),
            ];
        }

        return [
            'status' => 'not_initialized',
            'message' => 'Device requires initialization',
        ];
    }
}

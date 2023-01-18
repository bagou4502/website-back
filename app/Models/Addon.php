<?php

namespace App\Models;

use Illuminate\Container\Container;
use Znck\Eloquent\Traits\BelongsToThrough;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $sequence_id
 * @property string $action
 * @property string $payload
 * @property int $time_offset
 * @property bool $is_queued
 * @property bool $continue_on_failure
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $hashid
 */
class Addon extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'addon';


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'addon';


    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'tab',
        'tabroute',
        'new',
        'version',
        'sxcname'
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer:unique',
        'name' => 'string',
        'tab' => 'boolean',
        'tabroute' => 'string',
        'new' => 'boolean',
        'version' => 'float',
        "sxcname" => 'string'
    ];
}
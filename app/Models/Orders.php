<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'orders';


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';


    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'mollie_id',
        'status',
        'price',
        'token',
        'checkout'
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'product_id' => 'integer',
        'mollie_id' => 'string',
        'status' => 'string',
        'price' => 'integer',
        'token' => 'string',
        'checkout' => 'string'
    ];
}
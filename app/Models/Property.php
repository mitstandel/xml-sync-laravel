<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'properties';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'last_modify_date',
        'status',
        'agent_code',
        'unique_code',
        'price',
        'sold_price',
        'sold_date',
        'area',
        'frontage',
        'address',
        'category',
        'bedrooms',
        'bathrooms',
        'open_spaces',
        'headline',
        'description',
        'latitude',
        'longitude'
    ];
}

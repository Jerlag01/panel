<?php

namespace Pterodactyl\Models;

class Location extends Validable
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'location';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'locations';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Rules ensuring that the raw data stored in the database meets expectations.
     *
     * @var array
     */
    public static $validationRules = [
        'short' => 'required|string|between:1,60|unique:locations,short',
        'long' => 'required|string|between:1,255',
    ];

    /**
     * Gets the nodes in a specified location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function nodes()
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Gets the servers within a given location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function servers()
    {
        return $this->hasManyThrough(Server::class, Node::class);
    }
}

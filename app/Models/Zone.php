<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{

    protected $fillable = [
        'name',
        'slug'
    ];

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function buildings()
    {
        return $this->hasMany(Building::class);
    }

    public function units()
    {
        return $this->hasManyThrough(Unit::class, Building::class);
    }

    public function tenants()
    {
        return $this->hasManyThrough(Tenant::class, Unit::class);
    }
}

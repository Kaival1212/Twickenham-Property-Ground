<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{

    protected $fillable = [
        'name',
        'slug',
        'vacancy',
        'type',
        'address',
        'postcode',
        'building_id',
    ];


    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function documents(){
        return $this->morphMany('App\Models\Document', 'documentable');
    }

}


//$table->id();
//$table->string('name');
//$table->string('slug');
//$table->enum('vacancy', ['available', 'unavailable', 'pending'])->default('available');
//$table->string('type')->nullable();
//$table->string('address')->nullable();
//$table->string('postcode')->nullable();
//$table->foreignId('building_id')
//    ->constrained('buildings')
//    ->cascadeOnDelete();
//$table->timestamps();

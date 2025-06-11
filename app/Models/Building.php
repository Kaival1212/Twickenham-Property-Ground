<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Zone;

class Building extends Model
{

    protected $fillable = [
        'name',
        'slug',
        'zone_id',
        'street',
    ];


    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function documents(){
        return $this->morphMany(Document::class, 'documentable');
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}


//$table->id();
//$table->string('name');
//$table->string('slug')->unique();
//$table->foreignId('zone_id')
//    ->constrained('zones')
//    ->onDelete('cascade');
//$table->string('street')->nullable();
//$table->timestamps();

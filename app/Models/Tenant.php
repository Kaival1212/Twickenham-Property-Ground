<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'title',
        'first_name',
        'last_name',
        'email',
        'phone',
        'unit_id',
        'rent',
        'lease_start_date',
        'lease_end_date',
        'status',
        'rent_due_date'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}


//$table->string('title')->nullable();
//$table->string('first_name');
//$table->string('last_name');
//$table->string('email')->unique();
//$table->string('phone')->nullable();
//$table->foreignId('unit_id')->constrained()->onDelete('cascade');
//$table->decimal('rent', 10, 2)->default(0.00);
//$table->date('lease_start_date')->nullable();
//$table->date('lease_end_date')->nullable();
//$table->string('status')->default('active'); // e.g., active, inactive, terminated
//$table->date('rent_due_date')->nullable();

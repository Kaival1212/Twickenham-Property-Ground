<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{

    protected $fillable = [
        'name',
        'path',
        'folder_path',
        'document_type',
        'year',
        'size',
        'type',
        'documentable_type',
        'documentable_id',
        'visible_to_tenants',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

}


//$table->string('Name');
//$table->string('path');
//$table->string('type')->nullable();
//$table->string('size')->nullable();
//$table->morphs("documentable");

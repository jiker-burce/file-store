<?php

namespace Jiker\FileStore\Models;

use Illuminate\Database\Eloquent\Model;

class Storage extends Model
{
    protected $table = 'storages';

    protected $fillable = [
        'name',
        'description',
        'driver',
        'bucket',
    ];
}

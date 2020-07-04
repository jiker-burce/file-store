<?php

namespace Jiker\FileStore\Models;

use Illuminate\Database\Eloquent\Model;

class StorageScene extends Model
{
    protected $table = 'storage_scenes';

    protected $fillable = [
        'storage_id',
        'platform',
        'scene',
        'description',
    ];

    public function storage()
    {
        return $this->belongsTo(Storage::class, 'storage_id', 'id');
    }
}

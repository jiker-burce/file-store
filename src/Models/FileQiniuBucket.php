<?php

namespace Jiker\FileStore\Models;

use Illuminate\Database\Eloquent\Model;

class FileQiniuBucket extends Model
{
    protected $table = 'file_qiniu_buckets';

    protected $fillable = [
        'slug',
        'name',
        'bucket',
        'domain',
        'visibility',
        'access_key',
        'secret_key',
    ];
}

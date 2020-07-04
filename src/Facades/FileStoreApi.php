<?php
/**
 * Created by PhpStorm.
 * User: gujinhe
 * Date: 2020/7/3
 * Time: 11:54 PM
 */
namespace Jiker\FileStore\Facades;

use Illuminate\Support\Facades\Facade;

class FileStoreApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'jiker_file_store';
    }
}

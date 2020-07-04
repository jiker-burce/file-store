<?php
/**
 * Created by PhpStorm.
 * User: gujinhe
 * Date: 2020/7/3
 * Time: 2:47 PM
 */
use Illuminate\Http\Request;

if (!isset($router)) {
    $router = $this->app->router;
}

$router->group([
    'prefix' => 'api/file',
    'namespace' => 'Jiker\FileStore\Controllers\Api',
    'middleware' => ['cors', 'auth:api']
], function ($router) {
    // 文件
    $router->get('config', 'FileController@config');
    $router->post('upload', 'FileController@upload');
    $router->post('qiniu-upload-token', 'FileController@qiniuUploadToken');
    $router->post('qiniu-file-store', 'FileController@qiniuFileStore');
});

$router->group([
    'prefix' => 'api/admin/pass-through/file',
    'namespace' => 'Jiker\FileStore\Controllers\Api',
    'middleware' => ['auth.sign']
], function ($router) {
    $router->get('config', 'FileController@config');
    $router->post('upload', 'FileController@upload');
    $router->post('qiniu-upload-token', 'FileController@qiniuUploadToken');
    $router->post('qiniu-file-store', 'FileController@qiniuFileStore');
    $router->post('wechat/rq-code', 'WechatController@QRCodeFileStore');
});

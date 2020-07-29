<?php

namespace Jiker\FileStore\Managers;

use Qiniu;
use Illuminate\Support\Str;
use Jiker\FileStore\Models\WechatApp;

class FileStoreApiManager
{
    public function generateMiniProgramQRCode($wxMiniAppId, $bucket, $path, $filename, $space)
    {
        // 生成小程序二维码
        $app      = WechatApp::getMiniProgramApp($wxMiniAppId);
        $res      = $app->app_code->get($path);  // 获取二维码文件流
        $fileName = $filename . '.jpg';
        $path     = 'storage/images/qr_code/' . $wxMiniAppId;
        $res->save($path, $fileName);
        $localFullPath = $path . '/' . $fileName;
        
        // 上传七牛 并存储信息至 upload_files 表
        $bu            = Qiniu::bucket($bucket);
        $extension     = $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $qiniuFullPath = $this->getQiniuFullPath($space, 'qr_code_files', $extension);
        $uploadFile    = $bu->putFile($qiniuFullPath, $localFullPath);
        
        $uploadFile->update([
            'space' => $space,
            'title' => $filename,
        ]);
        
        // 删除本地文件
        unlink($localFullPath);
        
        return $uploadFile;
    }
    
    public function generateFileFromUrl($bucket, $space, $folder, $url)
    {
        $fd = file_get_contents($url);
        $fileName = last(explode('/',$url));
        if (empty($fileName)) { // 有的链接后缀不包含文件名和图片后缀，需要单独处理
            $fileName = Str::random(10) . '.jpg';
        }
        $localFullPath = storage_path('app/images/') . $fileName;
        file_put_contents($localFullPath,$fd);
    
        // 上传七牛 并存储信息至 upload_files 表
        $bu            = Qiniu::bucket($bucket);
        $extension     = $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $qiniuFullPath = $this->getQiniuFullPath($space, $folder, $extension);
        $uploadFile    = $bu->putFile($qiniuFullPath, $localFullPath);
    
        $uploadFile->update([
            'space' => $space,
            'title' => $fileName,
        ]);
    
        // 删除本地文件
        unlink($localFullPath);
    
        return $uploadFile;
    }
    
    protected function getQiniuFullPath($space, $folder, $extension)
    {
        if (empty($space)) {
            $space = 'default';
        }
        if (!preg_match('/\d$/', $space)) {
            // 不是以数字结尾的加上日期
            $space = $space . '/' . date('Y/md');
        }
        if (!empty($folder)) {
            $folder = '/' . $folder;
        }
        
        $path = $space . $folder;
        $name = Str::random(40);
        if (!empty($extension)) {
            $name .= '.' . $extension;
        }
        $full = $path . '/' . $name;
        
        return $full;
    }
}

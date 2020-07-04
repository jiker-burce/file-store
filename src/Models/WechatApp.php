<?php

namespace Jiker\FileStore\Models;


use Illuminate\Database\Eloquent\Model;

class WechatApp extends Model
{
    protected $table = 'wechat_apps';

    protected $fillable = [
        'type',
        'name',
        'original_id',
        'app_id',
        'secret',
        'token',
        'aes_key',
        'payment_merchant_id',
        'payment_key',
        'payment_cert_path',
        'payment_key_path',
    ];

    protected $casts = ['scopes' => 'json'];
    
    public static function getMiniProgramApp($appId)
    {
        $wechatApp = self::where('app_id', $appId)->first();
        $config = [
            'app_id' => $wechatApp->app_id,
            'secret' => $wechatApp->secret,
            
            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            
            'log' => [
                'level' => 'debug',
                'file' => storage_path('logs') . '/' . $appId . '/wechat.log',
            ],
        ];
        return \EasyWeChat\Factory::miniProgram($config);
    }
}

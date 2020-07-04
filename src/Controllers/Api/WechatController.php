<?php

namespace Jiker\FileStore\Controllers\Api;

use App\Http\Controllers\Controller;
use Qiniu;
use Validator;
use Jiker\FileStore\Managers\FileStoreApiManager;
use Illuminate\Http\Request;

class WechatController extends Controller
{
    public function QRCodeFileStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wx_mini_app_id' => 'required',
            'bucket' => 'required',
            'path' => 'required',
            'filename' => 'required',
            'space' => 'required'
        ], [
            'wx_mini_app_id.required' => '请指定APP_ID',
            'bucket.required' => '请指定七牛bucket',
            'path.required' => '请指定跳转路由',
            'filename.required' => '请指定文件名称',
            'space.required' => '空间名称（子项目）'
        ]);
    
        if ($validator->fails()) {
            return $this->error(1,$validator->errors()->first());
        }
        
        $data = $request->all();
        
        $uploadFile = (new FileStoreApiManager())
            ->generateMiniProgramQRCode(
                $data['wx_mini_app_id'],
                $data['bucket'],
                $data['[path'],
                $data['filename'],
                $data['space']
                );
        
        return $this->success($uploadFile->toArray());
    }
}

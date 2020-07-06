<?php

namespace Jiker\FileStore\Controllers\Api;

use URL;
use Qiniu;
use Storage;
use TimStorage;
use RuntimeException;
use Illuminate\Support\Facades\Validator;
use App\Models\UploadFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Jiker\FileStore\Models\StorageScene;
use Illuminate\Http\Request;
use Jiker\FileStore\Models\FileQiniuBucket;
use App\Http\Controllers\Controller;

class FileController extends Controller
{
    // 验证地址是否有效
    public function private(Request $request)
    {
        $path = $request->header('X-Original-Path');
        if (empty($path)) {
            abort(403);
        }

        $url = parse_url($path);
        parse_str(Arr::get($url, 'query'), $query);

        $r = new Request($query, [], [], [], [], [
            'HTTP_HOST' => Arr::get($url, 'host'),
            'REQUEST_URI' => Arr::get($url, 'path'),
            'HTTPS' => Arr::get($url, 'scheme') === 'https' ? 'on' : 'off',
        ]);

        if (URL::hasValidSignature($r)) {
            return;
        }

        abort(401);
    }

    /**
     * @api {get} /file/config 上传文件
     * @apiName Config
     * @apiGroup File
     *
     * @apiParam {String} [platform] 平台
     * @apiParam {String} [scene] 场景
     *
     * @apiUse Header_OAuth
     *
     * @apiUse Error_Example
     *
     * 优先级
     *   1.platform:匹配,scene:匹配
     *   2.platform:匹配,scene:default
     *   3.platform:default,scene:default
     *   4.filesystems.default
     */
    public function config(Request $request)
    {
        $data = $this->validate($request, [
            'platform' => 'required',
            'scene' => 'required',
        ]);

        $scene = StorageScene::where('platform', $data['platform'])
            ->where('scene', $data['scene'])
            ->first();
        if (empty($scene)) {
            $scene = StorageScene::where('platform', $data['platform'])
                ->where('scene', 'default')
                ->first();
        }
        if (empty($scene)) {
            $storage = TimStorage::bucket()->info();
        } else {
            $scene->load('storage');
            $storage = $scene->storage;
        }
        if (empty($storage)) {
            abort(500, 'Storage Config Error');
        }

        $response = response()->json([
            'storage' => $storage->name,
            'driver' => $storage->driver,
            'bucket' => $storage->bucket,
        ]);
        if (! empty($request->input('callback'))) {
            $response->setCallback($request->input('callback'));
        }

        return $response;
    }

    /**
     * @api {post} /file/upload 上传文件
     * @apiName Upload
     * @apiGroup File
     *
     * @apiParam {File} file 文件
     * @apiParam {File} [thumbnail] 文件的缩略图(只接受图片格式)
     * @apiParam {String} [space="default"] 空间
     * @apiParam {String} [folder] 文件夹
     * @apiParam {String} [accept="*"] 文件接受的类型
     * @apiParam {String} [title] 文件自定义名称
     * @apiParam {String} [bucket="public"] 桶
     *
     * @apiUse Header_OAuth
     *
     * @apiUse Error_Example
     */
    public function upload(Request $request)
    {
        $fileRule = 'required|file';
        if (! empty($request->input('accept'))) {
            $fileRule .= '|mimetypes:'.$request->input('accept');
        }

        $this->validate($request, [
            'file' => $fileRule,
            'thumbnail' => 'nullable|mimetypes:image/*',
        ], [], [
            'file' => '文件',
            'thumbnail' => '缩略图',
        ]);

        $bucket = $request->input('bucket');
        if (empty($bucket)) {
            $bucket = 'public';
        }
        $driver = config('filesystems.disks.'.$bucket.'.driver');
        if ($driver !== 'local') {
            abort(403);
        }
        $file = $request->file('file');
        // 如果传了文件名则使用传递的文件名
        $name = $request->input('name');
        if (! empty($name)) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
        } else {
            $name = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
        }

        if (! empty($request->input('service'))) {
            $saveInfo = $this->saveInfoByService($request);
        } else {
            $space = trim($request->input('space'), '/');
            $folder = trim($request->input('folder'), '/');

            $saveInfo = $this->saveInfoDefault($space, $folder, $extension);
        }

        $mime = $file->getMimeType();
        $size = $file->getClientSize();
        $title = $request->input('title');
        $hash = hash_file('sha1', $file->getRealPath());

        $path = Storage::disk($bucket)->putFileAs($saveInfo['path'], $file, $saveInfo['name']);
        $fullPath = Storage::disk($bucket)->url($saveInfo['full']);

        $f = UploadFile::create([
            'driver' => 'local',
            'bucket' => $bucket,
            'user_id' => $request->input('user_id',null),
            'title' => $title,
            'name' => $name,
            'extension' => $extension,
            'path' => $path,
            'size' => $size,
            'mime' => $mime,
            'hash' => $hash,
            'status' => 1,
        ]);

        return response()->json([
            'message' => '上传成功',
            'id' => $f->id,
            'title' => $title,
            'name' => $name,
            'display_name' => $f->name(),
            'extension' => $extension,
            'mime' => $mime,
            'size' => $size,
            'path' => $fullPath,
        ]);
    }

    /**
     * @api {post} /file/qiniu-upload-token 获取七牛上传文件 token
     * @apiName QiniuUploadToken
     * @apiGroup File
     *
     * @apiParam {String} [bucket] 桶
     * @apiParam {String} file_name 文件原始名称
     * @apiParam {String} [space="default"] 空间
     * @apiParam {String} [folder] 文件夹
     * @apiParam {String} [accept="*"] 接受的文件类型
     *
     * @apiUse Header_OAuth
     *
     * @apiUse Error_Example
     */
    public function qiniuUploadToken(Request $request)
    {
        $slugs = FileQiniuBucket::pluck('slug')->toArray();
        if (empty($slugs)) {
            abort(403);
        }
        $this->validate($request, [
            'bucket' => 'nullable|in:'.implode(',', $slugs),
            'file_name' => 'required',
        ]);

        // 构造文件存放路径
        $original_name = $request->input('file_name');
        if (! empty($request->input('service'))) {
            $saveInfo = $this->saveInfoByService($request);
        } else {
            $space = trim($request->input('space'), '/');
            $folder = trim($request->input('folder'), '/');
            $extension = pathinfo($original_name, PATHINFO_EXTENSION);

            $saveInfo = $this->saveInfoDefault($space, $folder, $extension);
        }
        $key = $saveInfo['full'];

        // 额外配置
        $config = [];
        if (! empty($request->input('accept'))) {
            $config['mimeLimit'] = implode(';', explode(',', $request->input('accept')));
        }
        list($bucketInfo, $token) = Qiniu::bucket($request->input('bucket'))->token($saveInfo['full'], $config);
        $url = [
            'preview' => Qiniu::bucket($bucketInfo->slug)->url($saveInfo['full'], 3600),
            'download' => Qiniu::bucket($bucketInfo->slug)->url($saveInfo['full'].'?'.http_build_query(['attname' => $original_name]), 3600),
        ];
        $bucket = $bucketInfo->slug;

        return response()->json(compact('bucket', 'original_name', 'extension', 'key', 'token', 'url'));
    }

    /**
     * @api {post} /file/qiniu-file-store 保存上传到七牛的文件
     * @apiName QiiuFileStore
     * @apiGroup File
     *
     * @apiParam {String} [bucket] 桶
     * @apiParam {String} path 文件路径
     * @apiParam {String} name 文件原始名称
     * @apiParam {String} [title] 文件自定义名称
     *
     * @apiUse Header_OAuth
     *
     * @apiUse Error_Example
     */
    public function qiniuFileStore(Request $request)
    {
        $slugs = FileQiniuBucket::pluck('slug')->toArray();
        if (empty($slugs)) {
            abort(403);
        }
    
        $validator = Validator::make($request->all(), [
            'bucket' => 'nullable|in:'.implode(',', $slugs),
            'path' => 'required',
            'space' => 'required',
            'name' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['data' => null, 'error_code' => 1, 'msg' => $validator->errors()->first()]);
        }
        
        $bucket = $request->input('bucket');
        if (empty($bucket)) {
            $bucket = $slugs[0];
        }
        $path = $request->input('path');
        $name = $request->input('name');
        $title = $request->input('title');
        $space = $request->input('space');
        $userId = $request->input('user_id',null);
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        $old = UploadFile::where([
            'driver' => 'qiniu',
            'bucket' => $bucket,
            'path' => $path,
        ])->count();
        if ($old) {
            abort(403);
        }
        $f = UploadFile::create([
            'driver' => 'qiniu',
            'bucket' => $bucket,
            'space' => $space,
            'path' => $path,
            'user_id' => $userId,
            'name' => $name,
            'title' => $title,
            'extension' => $extension,
            'status' => 1,
        ]);

        return response()->json([
            'message' => '上传成功',
            'id' => $f->id,
            'name' => $name,
            'extension' => $extension,
            'display_name' => $f->name(),
            'title' => $request->input('title'),
            'mime' => $request->input('mime'),
            'size' => $request->input('size'),
            'path' => Qiniu::bucket($bucket)->url($path, 3600),
        ]);
    }

    protected function saveInfoDefault($space, $folder, $extension)
    {
        if (empty($space)) {
            $space = 'default';
        }
        if (! preg_match('/\d$/', $space)) {
            // 不是以数字结尾的加上日期
            $space = $space.'/'.date('Y/md');
        }
        if (! empty($folder)) {
            $folder = '/'.$folder;
        }

        $path = $space.$folder;
        $name = Str::random(40);
        if (! empty($extension)) {
            $name .= '.'.$extension;
        }
        $full = $path.'/'.$name;

        return compact('path', 'name', 'full');
    }

    protected function saveInfoByService($request)
    {
        $service = $request->input('service');

        throw new RuntimeException("Upload Service {$service} Not Matched");
    }
}

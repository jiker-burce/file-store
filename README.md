## 文件通用包

### 作用
- 非jiker.com需要用到七牛上传时，每次需要加入多个模型和对上传数据的各种操作。
  - 文件上传
    - 获取config配置信息
      - 路由：/api/file/config
      - 参数：
        ```
        platform:project_default
        scene:project_public
        ```
      - 响应：
        ```
        {
            "storage": "project-qiniu-public",
            "driver": "qiniu",
            "bucket": "project-public"
        }
        ```
    - 生成上传token
      - 路由：/api/file/qiniu-upload-token
      - 参数：
        ```
        bucket:project-public
        file_name:abc.jpg
        space:_for_xxx_sub_project
        folder:stacks
        ```
      - 响应：
        ```
        {
            "bucket": "project-public",
            "original_name": "abc.jpg",
            "extension": "jpg",
            "key": "_for_xxx_sub_project/2020/0703/stacks/ahJOVSLcf8H92dBCeoTltYWtqBCLfHvnTHSmRQys.jpg",
            "token": "1sxqpZ0B-I4ZV1O07l4-JmnRduAF8XDK36l3HESX:Fo0GMB8Uc7dYXYxmBzzgJnVXfi4=:eyJyZXR1cm5Cb2R5Ijoie1wiYnVja2V0XCI6XCIkKGJ1Y2tldClcIixcImtleVwiOlwiJChrZXkpXCIsXCJldGFnXCI6XCIkKGV0YWcpXCIsXCJmbmFtZVwiOlwiJChmbmFtZSlcIixcImZzaXplXCI6XCIkKGZzaXplKVwiLFwibWltZVR5cGVcIjpcIiQobWltZVR5cGUpXCIsXCJlbmRVc2VyXCI6XCIkKGVuZFVzZXIpXCIsXCJwZXJzaXN0ZW50SWRcIjpcIiQocGVyc2lzdGVudElkKVwiLFwiZXh0XCI6XCIkKGV4dClcIixcInV1aWRcIjpcIiQodXVpZClcIn0iLCJzY29wZSI6InByb2RlZ3JlZS1wdWJsaWM6X2Zvcl9rY2VsbF9zdWJfcHJvamVjdFwvMjAyMFwvMDcwM1wvc3RhY2tzXC9haEpPVlNMY2Y4SDkyZEJDZW9UbHRZV3RxQkNMZkh2blRIU21SUXlzLmpwZyIsImRlYWRsaW5lIjoxNTkzODY2MTY5fQ==",
            "url": {
                "preview": "https://q2.cdn.project.com/_for_xxx_sub_project/2020/0703/stacks/ahJOVSLcf8H92dBCeoTltYWtqBCLfHvnTHSmRQys.jpg",
                "download": "https://q2.cdn.project.com/_for_xxx_sub_project/2020/0703/stacks/ahJOVSLcf8H92dBCeoTltYWtqBCLfHvnTHSmRQys.jpg?attname=abc.jpg"
            }
        }
        ```
    - 存储前端上传七牛后的UploadFile文件信息
      - 路由：/api/file/qiniu-file-store
      - 参数：
        ```
        // application/json
        
        {
          "bucket": "project-public",
          "name": "abc",
          "space": "_for_xxx_sub_project",
          "path": "_for_xxx_sub_project/2020/0704/stacks/a67Xh9nKQycU2mFBiRkSRu0nK201JjBSdH8qE7m8.jpg",
          "title": "abc"
        }
        ```
      - 响应：
        ```
        {
            "message": "上传成功",
            "id": 3,
            "name": "abc",
            "extension": "",
            "display_name": "abc",
            "title": "abc",
            "mime": null,
            "size": null,
            "path": "https://q2.cdn.project.com/_for_xxx_sub_project/2020/0704/stacks/a67Xh9nKQycU2mFBiRkSRu0nK201JjBSdH8qE7m8.jpg"
        }
        ```
  - 小程序二维码生成
    - 可以由后端或者前端指定参数就可以实现文件自动上传七牛，并且将UploadFile信息存放本地。
    - 调用方式
      - 调用类顶部
        - use FileStoreApi
      - FileStoreApi::generateMiniProgramQRCode()
        - 方法参数：
          - $wxMiniAppId 小程序appid
          - $bucket,     七牛空间
          - $path,       路径
          - $filename,   文件名
          - $space       子项目空间，如：_for_xxx_sub_project
    
### 注意
> 需要在开发项目中增加如下配置
- 在App\Models中加入文件，命名空间为`App\Models`。均可从本插件包中拷贝
  - Storage.php
  - UploadFile.php
- 路由中间件中需要加上
  - 'cors' => xxxx::class
  - AuthServiceProvider.php
    - 采用Jwt解析 Bearer token
  - config/auth.php
    - driver 需要调整为jwt

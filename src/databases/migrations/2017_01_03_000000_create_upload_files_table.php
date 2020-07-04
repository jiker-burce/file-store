<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUploadFilesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('upload_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('driver')->comment('驱动');
            $table->string('bucket')->comment('空间');
            $table->string('space')->nullable()->comment('子项目来源');
            $table->string('path', 2000)->comment('路径');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户');
            $table->string('title', 2000)->nullable()->comment('标题');
            $table->string('name', 2000)->nullable()->comment('原始标题');
            $table->string('extension')->nullable()->comment('后缀');
            $table->float('size', 20, 2)->nullable()->comment('大小');
            $table->string('mime')->nullable()->comment('MIME');
            $table->string('hash')->nullable()->comment('MD5');
            $table->unsignedTinyInteger('status')->default(1)->comment('文件状态 1:正常 2:未完成');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('upload_files');
    }
}

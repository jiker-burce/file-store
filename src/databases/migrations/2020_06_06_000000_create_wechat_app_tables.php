<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatAppTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('wechat_apps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('type')->default(1)->comment('应用类型 1:服务号 2:订阅号 11:小程序');
            $table->string('name')->comment('应用名称');
            // 基本信息
            $table->string('original_id')->nullable()->comment('微信原始 ID');
            $table->string('app_id')->comment('应用 ID');
            // 公众号
            $table->string('secret')->nullable()->comment('应用密钥');
            $table->string('token')->nullable()->comment('令牌');
            $table->string('aes_key')->nullable()->comment('消息加解密密钥');
            // 支付
            $table->string('payment_merchant_id')->nullable()->comment('支付商户 ID');
            $table->string('payment_key')->nullable()->comment('支付key');
            $table->string('payment_cert_path')->nullable()->comment('支付证书路径');
            $table->string('payment_key_path')->nullable()->comment('支付证书路径');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('wechat_apps');
    }
}

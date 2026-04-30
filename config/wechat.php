<?php
/**
 * 配置文件
 *
 * @author 耐小心<i@naixiaoxin.com>
 * @copyright 2017-2018 耐小心
 */

return [
    /*
      * 默认配置，将会合并到各模块中
      */
    'default'         => [
         'guzzle' => [
            //'timeout' => 3.0, // 超时时间（秒）
            'verify' => false, // 关掉 SSL 认证（强烈不建议！！！）
        ],
        'http' => [
            'verify' => false, // 关掉 SSL 认证（强烈不建议！！！）
        ],
        /*
         * 指定 API 调用返回结果的类型：array(default)/object/raw/自定义类名
         */
        'response_type' => 'array',
        /*
         * 使用 ThinkPHP 的缓存系统
         */
        'use_tp_cache'  => true,
        /*
         * 日志配置
         *
         * level: 日志级别，可选为：
         *                 debug/info/notice/warning/error/critical/alert/emergency
         * file：日志文件位置(绝对路径!!!)，要求可写权限
         */
        'log'           => [
            'level' => env('WECHAT_LOG_LEVEL', 'debug'),
            'file' => env('WECHAT_LOG_FILE', app()->getRuntimePath()."log/wechat.log"),
        ],
    ],

    //公众号
    'official_account' => [
        'default' => [
            // AppID
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID', config('setting.account_appid')),
            // AppSecret
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET', config('setting.account_secret')),
            // Token
            'token' => env('WECHAT_OFFICIAL_ACCOUNT_TOKEN', config('setting.account_token')),
            // EncodingAESKey
            'aes_key' => env('WECHAT_OFFICIAL_ACCOUNT_AES_KEY', config('setting.account_aeskey')),
            /*
             * OAuth 配置
             *
             * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
             * callback：OAuth授权完成后的回调页地址(如果使用中间件，则随便填写。。。)
             */
            'oauth' => [
               'scopes'   => array_map('trim',
                   explode(',', env('WECHAT_OFFICIAL_ACCOUNT_OAUTH_SCOPES', 'snsapi_base'))),
               'callback' => createUrl('api/Payorder/oauthcallback'),
            ],
        ],
    ],

    //第三方开发平台
    //'open_platform'    => [
    //    'default' => [
    //        'app_id'  => env('WECHAT_OPEN_PLATFORM_APPID', ''),
    //        'secret'  => env('WECHAT_OPEN_PLATFORM_SECRET', ''),
    //        'token'   => env('WECHAT_OPEN_PLATFORM_TOKEN', ''),
    //        'aes_key' => env('WECHAT_OPEN_PLATFORM_AES_KEY', ''),
    //    ],
    //],

    // 小程序
    'mini_program'     => [
       'default' => [
           'app_id'  => env('WECHAT_MINI_PROGRAM_APPID', config('setting.app_id')),
           'secret'  => env('WECHAT_MINI_PROGRAM_SECRET', config('setting.app_secret')),
           'token'   => env('WECHAT_MINI_PROGRAM_TOKEN', ''),
           'aes_key' => env('WECHAT_MINI_PROGRAM_AES_KEY', ''),
       ],
    ],

    //支付
    'payment'          => [
       'default' => [
           'sandbox'    => env('WECHAT_PAYMENT_SANDBOX', false),
           'app_id'     => env('WECHAT_PAYMENT_APPID', config('setting.app_id')),
           'mch_id'     => env('WECHAT_PAYMENT_MCH_ID', config('setting.mch_id')),
           'key'        => env('WECHAT_PAYMENT_KEY', config('setting.secret_key')),
           'cert_path'  => env('WECHAT_PAYMENT_CERT_PATH', env('root_path').'public/cert/apiclient_cert.pem'),    // XXX: 绝对路径！！！！
           'key_path'   => env('WECHAT_PAYMENT_KEY_PATH', env('root_path').'public/cert/apiclient_key.pem'),      // XXX: 绝对路径！！！！
           'notify_url' => config('setting.notify_url'),                           // 默认支付结果通知地址
       ],
       'official_account'=>[
           'sandbox'    => env('WECHAT_PAYMENT_SANDBOX', false),
           'app_id'     => config('setting.account_appid'),
           'mch_id'     => env('WECHAT_PAYMENT_MCH_ID', config('setting.wxpay_mch_id')),
           'key'        => env('WECHAT_PAYMENT_KEY', config('setting.wxpay_secret_key')),
           'cert_path'  => env('WECHAT_PAYMENT_CERT_PATH', env('root_path').'public/cert/apiclient_cert.pem'),    // XXX: 绝对路径！！！！
           'key_path'   => env('WECHAT_PAYMENT_KEY_PATH', env('root_path').'public/cert/apiclient_key.pem'),      // XXX: 绝对路径！！！！
           'notify_url' => config('setting.wxpay_notify'),
       ],
    ],
    //分账设置
//    'profit'=>[
//        'mch_id'=>'1504578081',
//        'ratio'=>0.1,
//        'mch_name'=>'成都智派科技有限公司',//公司名称
//        'account'=>'3147517919@qq.com',#支付宝账号
//        'account_name'=>'成都智派科技有限公司',//公司名称
//    ]
    //企业微信
    //'work'             => [
    //    'default' => [
    //        'corp_id'  => 'xxxxxxxxxxxxxxxxx',
    //        'agent_id' => 100020,
    //        'secret'   => env('WECHAT_WORK_AGENT_CONTACTS_SECRET', ''),
    //        //...
    //    ],
    //],
];

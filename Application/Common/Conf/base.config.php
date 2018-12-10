<?php
return [
    'userType'=>[
        '0'=>"未分组用户",
        '1'=>"后台管理员",
        '2'=>"后台操作员",
        '3'=>"高级用户",
        '4'=>"普通用户",
    ],
    'userStatus'=>[
        "0"=>'未激活',
        "1"=>'激活',
        "2"=>'冻结',
        "3"=>'删除',
    ],
    'regFrom'=>[
        "0"=>'未知',
        "1"=>'前端',
        "2"=>'微信',
    ],
    'authority'=>[
        '1'=>['List','One'],
        '2'=>['List','One','Add'],
        '3'=>['List','One','Add','Edit'],
        '4'=>['List','One','Add','Edit','Del'],
        '5'=>['List','One','Add','Edit','Del','Export'],
        '6'=>['List','One','Add','Edit','Del','Export','Import'],
        '7'=>['All'],
    ],
    'finan_table' => [
        'v_debit',
        // 'v_liquidate',
    ],
];
//7 最大权限，6 增删改查导入导出 5 增删改查导出 4 增删改查 3 增改查 2 增查 1 查 ',
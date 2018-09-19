<?php
namespace Component\Controller;
class ReceivableController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Receivable');
    }
    function createOrder(){
        // `id` int(10) NOT NULL AUTO_INCREMENT,
        // `project_id` int(11) DEFAULT NULL COMMENT '关联项目id',
        // `contract_date` int(11) DEFAULT '0' COMMENT '合同日期',
        // `pay_date` int(11) DEFAULT '0' COMMENT '付款日期（财务收款）',
        // `pay_amount` double(10,2) DEFAULT NULL COMMENT '付款金额（财务收款）',
        // `advance` double(10,2) DEFAULT NULL COMMENT '预付款',
        // `advance_date` int(11) DEFAULT '0' COMMENT '预付款日期',
        // `surplus` double(10,2) DEFAULT NULL COMMENT '未付款',
        // `surplus_date` int(11) DEFAULT '0' COMMENT '未付日期',
        // `next_date` int(11) DEFAULT '0' COMMENT '下次付款日期',
        // `remark` varchar(500) DEFAULT '' COMMENT '财务备注',
        // `add_time` int(10) DEFAULT NULL COMMENT '添加时间',
        // `update_time` int(11) DEFAULT NULL COMMENT '跟新时间',
        // `status` smallint(3) DEFAULT NULL COMMENT '状态',
        // `process_level` smallint(3) DEFAULT NULL COMMENT '审核所处阶段',
        // `author` int(10) DEFAULT NULL COMMENT '作者id',
        // `examine` varchar(200) DEFAULT '' COMMENT '审核者id “,”隔开',
        $Info=[
            "class"=>$stockName,
            "name"=>$value['company'],
            "alias"=>$value['val'],
        ];
        $this->insert($Info);
    }
}
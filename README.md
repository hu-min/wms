# wms
开发说明：
*Modal.html文件
{$title} 弹窗标题，可以配置
$nodeAuth 当前节点的权限值 1-7
$processAuth['level'] 指定流程的级别
{$controlName} 控制名称，又xx分配
{$gettype} 请求的类型 Add和Edit
{$url} 请求的地址
{$btnTitle} 按钮标题

class：
input 中的modal-info 必须的，添加数据和修改数据时会根据此class获取值
status-group 按钮组
status-btn 中按钮触发事件，
active 表示当前按钮激活

save-info 保存和修改数据触发的按钮

1，更新城市表
2，更新立项关联场地
3，修改点击事件封装
4，修改选择框扩张数据
5，采购表新增预付款日期字段，承接模型字段
6，浮动窗口可拖动
7，采购表修改合同文件字段和新增报价文件字段
8，优化选择框允许局部选择器
9，成本录入

2018-08-06
1，新增支付表和发票表
2，取消浮窗遮罩点击关闭和浮窗移动只限制head
3，新增开发查看文件media组件，支持jpg,png,gif,pdf,office
4，成本录入
5，项目采购成本审核

2018-08-16
1，新建审批记录表
2，基础数据新增报销类别
3，新建个人报销主表和子表
4，个人报销添加和查询修改

2018-08-03
1，新增未登录的情况下粘贴某个url在登录后跳转到该地址的功能
2，更新审核表
3，审批流处理

审批流程思路：
1，插入 v_approve_log 审批记录；
2，在审批类型为1（批准）的情况下判断当前审批人在审批流程中的位置，如果位置小于做大位置，那么 v_expense_sub 状态改成2（审批中），如 果审批类型为拒绝或者驳回则保留status 为该值；
3，统计 v_expense_sub 数量，判断 v_approve_log 表中该审批人 status = 1 的数量 如果审批数量==申请数量，修改 v_expense 审批级别为当前审批者的place 
4，如果审批状态出现 大于1 则 v_expense 中的status 改为 对应的status 否则除非 place 等于alllevel 否则status = 2 最终状态为1 
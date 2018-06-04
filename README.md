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
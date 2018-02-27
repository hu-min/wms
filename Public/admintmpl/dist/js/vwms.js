/** 
 * @Author: vition 
 * @Date: 2018-01-23 00:41:37 
 * @Desc: get方式获取数据 
 */    
function get(url,datas,callBack){
    asyncs=arguments[3]!=undefined?arguments[3]:true;
    $.ajax({
        url:url,
        type:'get',
        dataType:'json',
        data:datas,
        async:asyncs,
        success:callBack,
    })
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:43:43 
 * @Desc: post发送 
 */}
function post(url,datas,callBack){
    asyncs=arguments[3]!=undefined?arguments[3]:true;
    $.ajax({
        url:url,
        type:'post',
        dataType:'json',
        data:datas,
        async:asyncs,
        success:callBack,
    })
}

var datas={};
var filesData={};
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 18:19:11 
 * @Desc: 所有搜索按钮触发事件 
 */
$(document).on("click",".search-list,.vpage",function(){
    datas={}
    var page=$(this).data('page')
    if(page>0){
        datas['p']=page;
        var url=$(this).parents('.page-div').data("url");
        var con=$(this).parents('.page-div').data("con");
        var reqtype=$(this).parents('.page-div').data("reqtype");
    }else{
        var url=$(this).data("url");
        var con=$(this).data("con");
        var reqtype=$(this).data("reqtype");
    }
    var table=con+"Table";
    var page=con+"Page";
    datas.reqType=reqtype;
    eval(con+"SearchFuns()");//对不同的id设置不同的发送数据
    searchFun(url,datas,table,page);
    
})

function searchFun(url,datas,table,page){
    get(url,datas,function(result){
        if(result.errCode==0){
            $("#"+table).html(result.table);
            $("#"+page).html(result.page);
        }else{
            alert(result.error);
        }
    })
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 18:19:36 
 * @Desc: 所有编辑，添加，触发弹出modal事件 
 */
$(document).on("click",".info-edit",function(){

    var target=$(this).data("target");
    var title=$(this).data("title");
    var reqtype=$(this).data("reqtype");
    var show=$(this).data("show");
    var con=$(this).data("con");
    
    $(target).find('.modal-title').text(title)
    $(target).find('.save-info').text(title)
    $(target).find('.save-info').data("reqtype",reqtype)
    if(show=='One'){//编辑要获取数据
        datas={}
        var id=$(this).data("id");
        var url=$(this).data("url");
        
        datas.reqType=con+show;
        datas.id=id
        get(url,datas,function(result){
            if(result.errCode==0){
                eval(con+"ShowFuns(result.info)");//对不同的模块设置不同的响应数据

            }else{
                alert(result.error);
            }
        })
    }else{//新建要重置数据
        $(target).find(".modal-info").val("");
        eval(con+"ReFuns()");//对不同的模块的modal数据重置
    }
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 18:19:54 
 * @Desc: 所有状态选择按钮事件 
 */
$(document).on("click",'.status-btn',function(){
    $(this).parents(".status-group").children(".status-btn").removeClass("active");
    $(this).addClass("active");
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:09:37 
 * @Desc: 所有重置按钮 
 */
$(document).on("click",'.search-refresh',function(){
    $(this).parents(".search-body").find(".search-info").val("");
    var con=$(this).data("con")
    eval(con+"ResetFuns()");//弥补不足
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:38:31 
 * @Desc: 保存数据、新增或修改 
 */
$(document).on("click",'.save-info',function(){
    datas={}
    var url=$(this).data("url");
    var reqtype=$(this).data("reqtype");
    var con=$(this).data("con");
    var isModal=$(this).data("modal");
    var search=con+"-search";
    var parent=$(this).parents(".modal").attr("id")
    
    datas.reqType=con+reqtype;
    eval(con+"InfoFuns()");//对不同的id设置不同的发送数据
    
    if(JSON.stringify(filesData)!="{}"){
        datas['filesData']=filesData
    }
    // console.log(datas);
    post(url,datas,function(result){
        if(result.errCode==0){
            datas={}
            var url=$("#"+search).data("url");
            var con2=$("#"+search).data("con");
            if(con2==undefined){
                con2=con;
            }
            // console.log(search);
            var reqtype=$("#"+search).data("reqtype");
            var table=con2+"Table";
            var page=con2+"Page";
            datas.reqType=reqtype;
            eval(con2+"SearchFuns()");//对不同的id设置不同的发送数据
            if(isModal){
                searchFun(url,datas,table,page)
                $(tabId+" #"+parent).modal('toggle')
            }
        }else{
            alert(result.error)
        }
        
    });
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-28 22:28:38 
 * @Desc: 上传文件到files 
 */
$(document).on("change",".fileupdate",function(){
    var input=$(this).data("input");
    var imgName=this.files[0].name
    var slefs=$(this)
    var reader = new FileReader();
    reader.readAsDataURL(this.files[0])
    reader.onloadend = function () {  
        $(input).val(imgName);
        filesData[encodeURI(imgName)]=this.result
    };
})
$(document).on("click",'.tree-plus',function(){
    var treeId=$(this).data("id")
    $(treeId).treeview('collapseAll', { silent: true });
})
$(document).on("click",'.tree-minus',function(){
    var treeId=$(this).data("id")
    $(treeId).treeview('expandAll', { silent: true });
})
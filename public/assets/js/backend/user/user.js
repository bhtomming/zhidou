// const { Config } = require("parse");

define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-datetimepicker'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index?level=2',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });




            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'user_id',
                sortName: 'user.user_id',
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'user_id', title: __('User_Id'), sortable: true},
                        {field: 'username', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'realname', title: '姓名', operate: 'LIKE'},
                        {field: 'discount', title: '下级', operate: 'LIKE'},
                        {field: 'status', title: __('Status'), operate: 'LIKE',formatter:statusFormatter},
                        {field: 'year', title: '总提现', operate: 'LIKE'},
                        {field: 'sex', title: '已提现', operate: 'LIKE'},
                        {field: 'ke', title: '可提现', operate: 'LIKE',formatter:keFormatter},
                        {field: 'name', title: '安置人', operate: 'LIKE'},
                        {field: 'fzjc', title: '报单人', operate: 'LIKE'},
                        {field: 'bingshi', title: '直推人', operate: 'LIKE'},
                        {field: 'createtime', title: '加入时间', operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'),formatter:operateFormatter,events:operateEvents},
                        {field: 'isorder',title:"隐藏",visible:false},

                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        list: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    list_url: 'user/user/list?',
                }
            });

            var table = $("#table_list");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.list_url,
                pk: 'user_id',
                sortName: 'user.user_id',
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'user_id', title: __('User_Id'), sortable: true},
                        {field: 'username', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'realname', title: '姓名', operate: 'LIKE'},
                        {field: 'discount', title: '下级', operate: 'LIKE'},
                        {field: 'status', title: '等级', operate: 'LIKE',formatter:statusFormatter},
                        {field: 'year', title: '总提现', operate: 'LIKE'},
                        {field: 'sex', title: '已提现', operate: 'LIKE'},
                        {field: 'ke', title: '可提现', operate: 'LIKE',formatter:keFormatter},
                        {field: 'name', title: '安置人', operate: 'LIKE'},
                        {field: 'fzjc', title: '报单人', operate: 'LIKE'},
                        {field: 'bingshi', title: '直推人', operate: 'LIKE'},
                        {field: 'createtime', title: '加入时间', operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'),formatter:operateFormatterList,events:operateEvents},
                        {field: 'isorder',title:"隐藏",visible:false},

                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },

        search:function () {
            $('#date_from').datetimepicker();
            $('#date_to').datetimepicker();

            $('.do-search').on('click',function () {


                let username = $('#c_username').val();

                if(username==""){
                    alert("姓名或手机号码不能为空");
                    return;
                }else{

                    $('#add-form').submit();

                }

            });


        }

    };
    return Controller;
});

window.operateEvents = {
    "click #checkUser":function(e,value,row,index){
        window.location.href = Config.moduleurl+'/user/user/checkuser?user_id='+row.user_id;
    },
    "click #bindTp":function(e,value,row,index){
        window.location.href = Config.moduleurl+'/user/user/bindtp?oppen_id='+row.oppen_id;
    },
    "click #um":function(e,value,row,index){
        window.location.href = Config.moduleurl+'/user/user/um?username='+row.username;
    },
    "click #uml":function(e,value,row,index){
        window.location.href = Config.moduleurl+'/user/user/uml?user_id='+row.user_id;
    },
    "click #upu":function(e,value,row,index){
        window.location.href = Config.moduleurl+'/user/user/upu?username='+row.username;
    },
    "click #addDown":function(e,value,row,index){
        window.location.href = Config.moduleurl+'/user/user/add?oppen_id='+row.oppen_id;
    },
    "click #del":function(e,value,row,index){
        var  b = confirm('确定停机？');
        if(!b){
            return ;
        }
    
        $.ajax({
            url:Config.moduleurl+'/user/user/delup',
            type:'post',
            data:{
                user_id:row.user_id,
                isorder:0
            },
            success:function(rs){
                // console.log(JSON.parse(rs))
                // console.log(eval('(' + rs + ')'))
                console.log(rs.code)
                if(rs.code==1){
                    alert("停机成功");
                    $('#table').bootstrapTable('refresh');
                }else{
                    showTip("停机失败！");
                }
            }
        })    },
    "click #delup":function(e,value,row,index){
        var  b = confirm('确定开机！开机后分佣正常发放！');
        if(!b){
            return ;
        }
        $.ajax({
            url:Config.moduleurl+'/user/user/delup',
            type:'post',
            data:{
                user_id:row.user_id,
                isorder:1
            },
            success:function(rs){
                if(rs.code==1){
                    alert("开机成功");
                    $('#table').bootstrapTable('refresh');
                }else{
                    showTip("开机失败！");
                }
            }
        })    },
}

function keFormatter(value,row,index){
    return row.year - row.sex
}

function operateFormatterList(value,row,index){
    var result = "";
    
    if(row.dzjb == 0){
         result += '<button id="checkUser" class="btn btn-default">审核</button>';
    }else if(row.dzjb == 1){
        result += '<span>已审核</span>';
    }else if(row.dzjb == 2){
        result += '<span>已驳回,原因:</span>'+'<span>'+row.bio+'</span>';
    }
   
    if(row.top_openid == null){
        result += '<button id="bindTp" class="btn btn-default">绑定上级</button>';
    }
    return result
}

function operateFormatter (value,row,index){
    var result = "";
    if(row.isorder == 1){
        result += '<button id="um" class="btn btn-default">更改佣金</button>';
        result += '<button id="uml" class="btn btn-default">佣金明细</button>';
        result += '<button id="upu" class="btn btn-default">修改</button>';
        if(row.discount < 2){
            result += '<button id="addDown" class="btn btn-default">添加</button>';
        }
        result += '<button id="del" class="btn btn-default">停机</button>';
    }

    if(row.isorder == 0){
        result += '<button id="delup" class="btn btn-default">开机</button>';
    }
    return result;
}

function statusFormatter(value){
    // var statusList = [];
    // statusList[0] = '-';
    // statusList[1] = '创客1';
    // statusList[2] = '创客2';
    // statusList[3] = '创客3';
    // statusList[4] = '创客4';
    // statusList[5] = '创客5';
    // statusList[6] = '店长1';
    // statusList[7] = '店长2';
    // statusList[8] = '店长3';
    // statusList[9] = '店长4';
    // statusList[10] = '店长5';
    // statusList[11] = '一星营业厅';
    // statusList[12] = '二星营业厅';
    // statusList[13] = '三星营业厅';
    // statusList[14] = '四星营业厅';
    // statusList[15] = '五星营业厅';
    // statusList[16] = '五星A营业厅';
    // statusList[17] = '五星AA营业厅';
    // statusList[18] = '五星AAA营业厅';
    // statusList[19] = '五星AAAA营业厅';
    // statusList[20] = '五星AAAAA营业厅';

    //return statusList[value];

    let status_text="专员";

    if(value >=1 && value <=5 || value == 1001){
        status_text = "创客";
    }else if(value >=6 && value<=10 || value == 1002){
        status_text = "店长";
    }else if(value >=11 && value<=20 || value == 1003){
        status_text = "经理";
    }else if(value >=21 && value<=30 || value == 1004){
        status_text = "总监";
    }else if(value >=31 && value<=40 || value == 1005){
        status_text = "总裁";
    }


    return status_text;

}


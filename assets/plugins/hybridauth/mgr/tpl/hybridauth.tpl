<script type="text/javascript">
var ehaConfig = {
    uid:[+uid+],
    ehaGridLoaded:false,
    url:'[+url+]'
};
var ehaGridColumns = [ [
    {
        field:'provider',
        title:_ehaLang['provider'],
        sortable:true,
        width:100,
        formatter: ehaHelper.escape
    },
    {
        field:'identifier',
        title:_ehaLang['identifier'],
        sortable:true,
        width:100,
        formatter: ehaHelper.escape
    },
    {
        field:'name',
        title:_ehaLang['name'],
        sortable:true,
        width:100,
        formatter: function(value, row) {
            var name = row.firstname + ' ' + row.lastname;
            return ehaHelper.escape(name);
        }
    },
    {
        field:'email',
        title:'Email',
        sortable:true,
        width:100,
        formatter: function(value, row) {
            var email = row.emailverified == '' ? value : row.emailverified;
            return ehaHelper.escape(email);
        }
    },
    {
        field:'createdon',
        title:_ehaLang['createdon'],
        align:'center',
        sortable:true,
        formatter:function(value) {
            sql = value.split(/[- :]/);
            d = new Date(sql[0], sql[1]-1, sql[2], sql[3], sql[4], sql[5]);
            year = d.getFullYear();
            month = d.getMonth()+1;
            day = d.getDate();
            hour = d.getHours();
            min = d.getMinutes();
            return ('0'+day).slice(-2) + '.' + ('0'+month).slice(-2) + '.' + year + '<br>' + ('0'+hour).slice(-2) + ':' + ('0'+min).slice(-2);
        }
    },
    {
        field:'action',
        width:40,
        title:'',
        align:'center',
        fixed:true,
        formatter:function(value,row,index){
            return '<a class="action delete" href="javascript:void(0)" onclick="ehaHelper.delete('+row.id+')" title="'+_ehaLang['delete']+'"><i class="fa fa-trash fa-lg"></i></a>';
        }
    }
] ];
(function($){
    $('#webUserPane').on('click','#eha-tab',function(){
        if (ehaConfig.ehaGridLoaded) {
            $('#ehaGrid').datagrid('reload');
            $(window).trigger('resize');
        } else {
            ehaHelper.init();
            ehaConfig.ehaGridLoaded = true;
        }
    });
    $(window).on('load', function(){
        if ($('#eha-tab')) {
            $('#eha-tab.selected').trigger('click');
        }
    });
    $(window).on('resize',function(){
        if ($('#eha-tab').hasClass('selected')) {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(function () {
                $('#EvoHybridAuth').width($('body').width() - 60);
                if (ehaConfig.ehaGridLoaded) {
                    $('#ehaGrid').datagrid('getPanel').panel('resize');
                }
            }, 300);
        }
    })
})(jQuery)
</script>
<div id="EvoHybridAuth" class="tab-page">
<h2 class="tab" id="eha-tab">[+tabName+]</h2>
</div>

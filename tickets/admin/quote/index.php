<?php
session_start();
if(!isset($_SESSION['login_id'])){
    header("Location: ../login.php");
    exit;
}
?>
<br>
<h5 class="">Request</h5>
<hr>
<div class="row">
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h5 class="card-title">Lista de solicitudes de requerimientos</h5>
                <!-- <div class="card-tools">
                    <button class="btn btn-flat btn-primary btn-sm" type="button" id="new_data"><span class="fa fa-plus"></span> New Category</button>
                </div> -->
            </div>
            <div class="card-body">
                <div class="container-fluid">
                    <table class="table table-striped table-bordered" id="quote_table">
                        <colgroup>
                            <col width="5%">
                            <col width="15%">
                            <col width="20%">
                            <col width="40%">
                            <col width="20%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha y Hora</th>
                                <th>Correo a</th>
                                <th>Servicios</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Define base URL para AJAX y modales
var _base_url_ = "http://localhost:8888/soporte-online/tickets/";

function start_loader(){
    $('body').append('<div id="preloader"></div>'); // ejemplo simple
}
function end_loader(){
    $('#preloader').remove();
}
function alert_toast(msg, type='success'){
    alert(msg); // puedes reemplazar con tu propio toast
}

function load_data(){
    start_loader();
    if ( $.fn.DataTable.isDataTable('#quote_table') ) {
        $('#quote_table').DataTable().destroy();
        $('#quote_table tbody').html('');
    }
    $.ajax({
        url:_base_url_+"classes/Master.php?f=load_quote",
        dataType: "json",
        error: err=>{
            console.log(err);
            alert_toast("Ha ocurrido un error",'error');
            end_loader();
        },
        success:function (resp){
            if(!!resp.status){
                if(resp.data.length > 0){
                    let data = resp.data;
                    var i = 1;
                    Object.keys(data).map((k)=>{
                        let tr = $("<tr></tr>");
                        tr.append('<td class="text-center">'+(i++)+'</td>');
                        tr.append('<td><b>'+data[k].date_created+'</b></td>');
                        tr.append('<td><b>'+data[k].email+'</b></td>');
                        tr.append('<td><span class="truncate" title="'+data[k].services+'">'+data[k].services+'</span></td>');
                        tr.append('<td class="text-center"><div class="btn-group">'+
                           ' <button type="button" class="btn btn-default dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">'+
                            'Acción'+
                            '<span class="sr-only">Toggle Dropdown</span>'+
                           '</button>'+
                           '<div class="dropdown-menu" role="menu">'+
                              '<a class="dropdown-item text-danger delete_data" data-id="'+data[k].id+'" href="javascript:void(0)"><span class="fa fa-trash text-danger"></span> Eliminar</a>'+
                           '</div>'+
                          '</div></td>');
                        $('#quote_table tbody').append(tr);
                    });
                } else {
                    $('#quote_table tbody').html('');
                }
            } else {
                alert_toast("Ha ocurrido un error",'error');
            }
            end_loader();
        },
        complete:function(){
            if(!$.fn.DataTable.isDataTable('#quote_table')){
                $('#quote_table').DataTable();
            }
            data_func();
        }
    });
}

function data_func(){
    $('.delete_data').click(function(){
        _conf('¿De verdad quieres eliminar esta información?','delete_data',[$(this).attr('data-id')]);
    });
}

function delete_data($id){
    start_loader();
    $.ajax({
        url:_base_url_+"classes/Master.php?f=delete_quote",
        method:'POST',
        data:{id:$id},
        dataType:'json',
        error: err=>{
            console.log(err);
            alert_toast("Ha ocurrido un error","error");
            end_loader();
        },
        success:function(resp){
            if(!!resp.status && resp.status == 'success'){
                alert_toast("Datos eliminados exitosamente.","success");
                $('.modal').modal('hide');
                end_loader();
                load_data();
            }
        }
    });
}

$(document).ready(function(){
    load_data();

    $('#new_data').click(function(){
        uni_modal('<span class="fa fa-plus"></span> Crear nueva categoría de servicio',
                  _base_url_+'admin/services/manage_category.php');
    });
});
</script>

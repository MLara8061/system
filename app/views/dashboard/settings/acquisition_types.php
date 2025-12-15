<br>
<div class="row">
	<div class="container-fluid">
		<div class="card card-outline card-primary">
			<div class="card-header">
				<h5 class="card-title">Tipos de Adquisición (CLAVE + Descripción)</h5>
				<div class="card-tools">
					<button class="btn btn-flat btn-primary btn-sm" type="button" id="new_data"><span class="fa fa-plus"></span> Nuevo Tipo</button>
				</div>
			</div>
			<div class="card-body">
				<div class="container-fluid">
					<table class="table table-striped table-bordered">
						<colgroup>
							<col width="5%">
							<col width="15%">
							<col width="60%">
							<col width="20%">
						</colgroup>
						<thead>
							<tr>
								<th>#</th>
								<th>CLAVE</th>
								<th>Descripción</th>
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
	if (typeof window.start_loader !== 'function') window.start_loader = function() {};
	if (typeof window.end_loader !== 'function') window.end_loader = function() {};

	function load_data(){
		if ( $.fn.DataTable.isDataTable('table') ) {
			$('table').DataTable().destroy();
			$('table tbody').html('')
		}
		start_loader();
		$.ajax({
			url:"public/ajax/action.php?action=load_acquisition_type",
			dataType: "json",
			error: err=>{
				console.log(err)
				alert_toast("Ha ocurrido un error",'error');
				end_loader();
			},
			success:function (resp){
				if(!!resp.status){
					if(resp.data.length > 0){
						let data = (resp.data);
						var i = 1;
						Object.keys(data).map((k)=>{
							let tr = $("<tr></tr>");
							tr.append('<td class="text-center">'+(i++)+'</td>')
							tr.append('<td><b>'+ (data[k].code || '') +'</b></td>')
							tr.append('<td><span class="truncate">'+ (data[k].name || '') +'</span></td>')
							tr.append('<td class="text-center"><div class="btn-group">'+
								' <button type="button" class="btn btn-default dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">'+
			                    	'Acción'+
									'<span class="sr-only">Toggle Dropdown</span>'+
								'</button>'+
								'<div class="dropdown-menu" role="menu" style="">'+
									'<a class="dropdown-item text-primary edit_data" data-id="'+(data[k].id)+'" href="javascript:void(0)"><span class="fa fa-edit"></span> Editar</a>'+
									'<div class="dropdown-divider"></div>'+
									'<a class="dropdown-item text-danger delete_data" data-id="'+(data[k].id)+'" href="javascript:void(0)"><span class="fa fa-trash text-fanger"></span> Eliminar</a>'+
								'</div>'+
							'</div></td>')
							$('table tbody').append(tr)
						})
					}else{
						$('table tbody').html('')
					}
					end_loader();
				}else{
					alert_toast("Ha ocurrido un error",'error');
					end_loader();
				}
			},
			complete:function(){
				data_func()
				$('table').dataTable();
				end_loader();
			}
		})
	}

	function data_func(){
		$('.edit_data').click(function(){
			uni_modal('<span class="fa fa-edit text-primary"></span> Editar Tipo de Adquisición','app/views/dashboard/settings/manage_acquisition_type.php?id='+$(this).attr('data-id'))
		})
		$('.delete_data').click(function(){
			_conf('Deseas eliminar estos datos?','delete_data',[$(this).attr('data-id')]);
		})
	}

	function delete_data($id){
		start_loader();
		$.ajax({
			url:"public/ajax/action.php?action=delete_acquisition_type",
			method:'POST',
			data:{id:$id},
			dataType:'json',
			error:err=>{
				console.log(err);
				alert_toast("Ha ocurrido un error","error");
				end_loader();
			},
			success:function(resp){
				if(!!resp.status && resp.status == 'success'){
					alert_toast("Datos eliminados exitosamente.","success");
					$('.modal').modal('hide');
					end_loader();
					load_data()
				}else if(resp && resp.status == 'in_use'){
					alert_toast("No se puede eliminar: está en uso","warning");
					end_loader();
				}
			}
		})
	}

	$(document).ready(function(){
		load_data()
		$('#new_data').click(function(){
			uni_modal('<span class="fa fa-plus"></span> Crear un nuevo tipo de adquisición','app/views/dashboard/settings/manage_acquisition_type.php')
		})
	})
</script>


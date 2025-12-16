
<style>
	.img-service{
		width:75px;
		height:75px;
		object-fit: contain;
	}
</style>
<div class="row">
	<div class="container-fluid">
		<div class="card card-outline card-primary">
		
			<div class="card-body">
				<div class="container-fluid">
					<table class="table table-striped table-bordered">
						<colgroup>
							<col width="5%">
							<col width="10%">
							<col width="15%">
							<col width="15%">
							<col width="30%">
							<col width="10%">
						</colgroup>
						<thead>
							<tr>
								<th>#</th>
								<th>Imagen</th>
								<th>Categoría</th>
								<th>Servicio</th>
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
	function load_data(){
		//start_loader();
		if ( $.fn.DataTable.isDataTable('table') ) {
			$('table').DataTable().destroy();
			$('table tbody').html('')
		}
		$.ajax({
			url:"public/ajax/action.php?action=load_service",
			dataType: "json",
			error: err=>{
				console.log(err)
				alert_toast("A ocurrido un error",'error');
				end_loader();
			},
			success:function (resp){
				// resp = JSON.parse(resp)
				if(!!resp.status){
					if(resp.data.length > 0){
						let data = (resp.data);
						var i = 1;
						Object.keys(data).map((k)=>{
						let tr = $("<tr></tr>");
							tr.append('<td class="text-center">'+(i++)+'</td>')
							// Usar imagen por defecto si no existe
							let imgSrc = data[k].img_path && data[k].img_path.trim() !== '' ? data[k].img_path : 'uploads/default.png';
							let imgTag = '<img src="'+imgSrc+'" class="img-thumbnail img-service" onerror="this.src=\'uploads/default.png\'" />';
							tr.append('<td>'+imgTag+'</td>')
							tr.append('<td><b>'+data[k].category+'</b></td>')
							tr.append('<td><b>'+data[k].service+'</b></td>')
							tr.append('<td><span class="truncate">'+data[k].description+'</span></td>')
							tr.append('<td class="text-center"><div class="btn-group">'+
	                   ' <button type="button" class="btn btn-default dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">'+
	                    	'Acción'+
	                      '<span class="sr-only">Toggle Dropdown</span>'+
	                    '</button>'+
	                    '<div class="dropdown-menu" role="menu" style="">'+
	                      '<a class="dropdown-item text-primary edit_data" data-id="'+(data[k].id)+'" href="javascript:void(0)"><span class="fa fa-edit"></span> Editar</a>'+
	                      '<div class="dropdown-divider"></div>'+
	                      '<a class="dropdown-item text-danger delete_data" data-id="'+(data[k].id)+'" href="javascript:void(0)"><span class="fa fa-trash text-fanger"></span> Eliminar</a>'+
	                   ' </div>'+
	                  '</div></td>')
							$('table tbody').append(tr)
						})
					}else{
							$('table tbody').html('')
					}
					end_loader();
				}else{
					alert_toast("A ocurrido un error",'error');
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
			uni_modal('<span class="fa fa-edit text-primary"></span> Editar Servicio','modals/manage_service.php?id='+$(this).attr('data-id'))
		})
		$('.delete_data').click(function(){
			const serviceId = $(this).attr('data-id');
			confirm_toast(
				'¿Estás seguro de eliminar este servicio? Esta acción no se puede deshacer.',
				function() { delete_data(serviceId); }
			);
		})
	}
	function delete_data($id){
		start_loader();
		$.ajax({
			url:"public/ajax/action.php?action=delete_service",
			method:'POST',
			data:{id:$id},
			dataType:'json',
			error:err=>{
				console.log(err);
				alert_toast("A ocurrido un error","error");
				end_loader();
			},
			success:function(resp){
				if(!!resp.status && resp.status == 'success'){
					alert_toast(" Datos eliminados exitosamente","success");
					$('.modal').modal('hide');
					end_loader();
					load_data()
				}
			}
		})
	}
	$(document).ready(function(){
		load_data()
		$('#new_data').click(function(){
			uni_modal('<span class="fa fa-plus"></span> Create New Service','modals/manage_service.php')
		})
	})
</script>


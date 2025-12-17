<!-- SweetAlert2 -->
<script src="assets/plugins/sweetalert2/sweetalert2.min.js"></script>
<!-- Toastr -->
<script src="assets/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="assets/plugins/select2/js/select2.full.min.js"></script>
<!-- Summernote -->
<script src="assets/plugins/summernote/summernote-bs4.min.js"></script>

<script>
(function($){
    var loaderSelector = '#page-loading-indicator';
    var hiddenClass = 'is-hidden';
    var loaderTemplate = '<div id="page-loading-indicator"><div class="spinner" aria-hidden="true"></div></div>';

    function ensureLoader() {
        var $loader = $(loaderSelector);
        if (!$loader.length) {
            $('body').prepend(loaderTemplate);
            $loader = $(loaderSelector);
        }
        return $loader;
    }

	$(document).ready(function() {
		// $('.datetimepicker').datetimepicker({
		//     format:'Y/m/d H:i',
		//     startDate: '+3d'
		// })

		$('.select2').select2({
			placeholder: "Selecciona aquí",
			width: "100%"
		})

        var $loader = ensureLoader();
        var hideLoader = function() {
            if (!$loader.hasClass(hiddenClass)) {
                $loader.addClass(hiddenClass);
            }
        };

        $(window).on('load', hideLoader);
        setTimeout(hideLoader, 800);

        $(document)
            .on('ajaxStart', function() {
                ensureLoader().removeClass(hiddenClass);
            })
            .on('ajaxStop', hideLoader);
	})
	window.start_load = function() {
		ensureLoader().removeClass(hiddenClass);
	}
	window.end_load = function() {
		ensureLoader().addClass(hiddenClass);
	}
	// Compatibilidad con vistas legacy que aún invocan start_loader/end_loader
	if (typeof window.start_loader !== 'function') window.start_loader = window.start_load;
	if (typeof window.end_loader !== 'function') window.end_loader = window.end_load;
	window.viewer_modal = function($src = '') {
		start_load()
		var t = $src.split('.')
		t = t[1]
		if (t == 'mp4') {
			var view = $("<video src='" + $src + "' controls autoplay></video>")
		} else {
			var view = $("<img src='" + $src + "' />")
		}
		$('#viewer_modal .modal-content video,#viewer_modal .modal-content img').remove()
		$('#viewer_modal .modal-content').append(view)
		$('#viewer_modal').modal({
			show: true,
			backdrop: 'static',
			keyboard: false,
			focus: true
		})
		end_load()

	}
	window.uni_modal = function($title = '', $url = '', $size = "") {
		start_load()
		$.ajax({
			url: $url,
			error: err => {
				console.log()
				alert("An error occured")
			},
			success: function(resp) {
				if (resp) {
					$('#uni_modal .modal-title').html($title)
					$('#uni_modal .modal-body').html(resp)
					if ($size != '') {
						$('#uni_modal .modal-dialog').addClass($size)
					} else {
						$('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md")
					}
					$('#uni_modal').modal({
						show: true,
						backdrop: 'static',
						keyboard: false,
						focus: true
					})
					end_load()
				}
			}
		})
	}
	window._conf = function($msg = '', $func = '', $params = []) {
		$('#confirm_modal #confirm').attr('onclick', $func + "(" + $params.join(',') + ")")
		$('#confirm_modal .modal-body').html($msg)
		$('#confirm_modal').modal('show')
	}
	var Toast = Swal.mixin({
		toast: true,
		position: 'top-end',
		showConfirmButton: false,
		timer: 5000
	});
	
	// Comentado el alert_toast antiguo - ahora se carga desde custom-alerts.js
	// window.alert_toast = function($msg = 'TEST', $bg = 'success') {
	// 	Toast.fire({
	// 		icon: $bg,
	// 		title: $msg
	// 	})
	// }
	
	$(function() {
		$('.summernote').summernote({
			height: 300,
			toolbar: [
				['style', ['style']],
				['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
				['fontname', ['fontname']],
				['fontsize', ['fontsize']],
				['color', ['color']],
				['para', ['ol', 'ul', 'paragraph', 'height']],
				['table', ['table']],
				['view', ['undo', 'redo', 'fullscreen', 'codeview', 'help']]
			]
		})

		// Fail-safe: si el formulario de ticket no trae botones (HTML truncado/plantilla vieja), inyectarlos.
		try {
			var $form = $('#manage_ticket');
			if ($form.length && !$form.closest('#uni_modal, #uni_modal_right').length) {
				var hasSubmit = $form.find('button[type="submit"]').length > 0;
				if (!hasSubmit) {
					var idVal = parseInt(($form.find('input[name="id"]').val() || '0'), 10) || 0;
					var isEdit = idVal > 0;
					var cancelHref = isEdit ? ('index.php?page=view_ticket&id=' + idVal) : 'index.php?page=ticket_list';
					var submitLabel = isEdit ? 'Guardar cambios' : 'Guardar Ticket';

					var $actions = $('<div class="bg-light border-top ticket-actions mt-3 pt-3" style="position: sticky; z-index: 1020;"></div>');
					var $row = $('<div class="d-flex justify-content-end align-items-center flex-wrap" style="gap: .5rem;"></div>');
					if (!isEdit) {
						$row.append('<button class="btn btn-secondary" type="reset"><i class="fas fa-redo"></i> Limpiar</button>');
					}
					$row.append('<a href="./' + cancelHref + '" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>');
					$row.append('<button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> ' + submitLabel + '</button>');
					$actions.append($row);

					// Offset por footer fijo (AdminLTE layout-footer-fixed)
					var $footer = $('.main-footer');
					var h = ($footer.length ? ($footer.outerHeight() || 0) : 0);
					$actions.css('bottom', h + 'px');

					// Insertar al final del formulario (preferir dentro del card-body si existe)
					var $cardBody = $form.find('.card-body').last();
					if ($cardBody.length) $cardBody.append($actions);
					else $form.append($actions);
				}
			}
		} catch (e) {
			// No-op
		}

		// Pre-cargar HTML en el editor (edición de ticket) sin romper el DOM
		try {
			if (typeof window.__ticket_description_html === 'string' && $('.summernote').length) {
				$('.summernote').summernote('code', window.__ticket_description_html);
				delete window.__ticket_description_html;
			}
		} catch (e) {
			// No-op
		}

	})
})(jQuery);
</script>

<!-- Bootstrap Bundle (incluye Popper) -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="assets/dist/js/adminlte.js"></script>

<!-- PAGE assets/plugins -->
<!-- jQuery Mapael -->
<script src="assets/plugins/jquery-mousewheel/jquery.mousewheel.js"></script>
<script src="assets/plugins/raphael/raphael.min.js"></script>
<script src="assets/plugins/jquery-mapael/jquery.mapael.min.js"></script>
<script src="assets/plugins/jquery-mapael/maps/usa_states.min.js"></script>
<!-- ChartJS -->
<script src="assets/plugins/chart.js/Chart.min.js"></script>

<!-- AdminLTE for demo purposes -->
<script src="assets/dist/js/demo.js"></script>
<!-- Sistema de Alertas Moderno -->
<script src="assets/js/custom-alerts.js"></script>
<!-- DataTables  & Plugins -->
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="assets/plugins/jszip/jszip.min.js"></script>
<script src="assets/plugins/pdfmake/pdfmake.min.js"></script>
<script src="assets/plugins/pdfmake/vfs_fonts.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

</body>
</html>
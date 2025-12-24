<?php $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>

<!-- Sistema de notificaciones moderno -->
<script src="<?php echo $base; ?>/assets/js/notification.js"></script>
<!-- SweetAlert2 -->
<script src="<?php echo $base; ?>/assets/plugins/sweetalert2/sweetalert2.min.js"></script>
<!-- Toastr -->
<script src="<?php echo $base; ?>/assets/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="<?php echo $base; ?>/assets/plugins/select2/js/select2.full.min.js"></script>
<!-- Summernote -->
<script src="<?php echo $base; ?>/assets/plugins/summernote/summernote-bs4.min.js"></script>

<!-- ticket-failsafe:v4 2025-12-17 -->

<script>
(function($){
    var loaderSelector = '#page-loading-indicator';
    var hiddenClass = 'is-hidden';
    var loaderTemplate = '<div id="page-loading-indicator"><div class="spinner" aria-hidden="true"></div></div>';

	function injectTicketActionsFailSafe() {
		try {
			var $form = $('#manage_ticket');
			if (!$form.length) return;
			if ($form.closest('#uni_modal, #uni_modal_right').length) return;

			// Evitar duplicados: si ya inyectamos la nuestra
			if (document.getElementById('ticket-actions-failsafe')) return;

			var $existingActions = $form.find('.ticket-actions').not('#ticket-actions-failsafe');

			var $submits = $form.find('button[type="submit"], input[type="submit"]');
			var visibleSubmits = $submits.filter(':visible').length;

			var footerH = (function(){
				var $footer = $('.main-footer');
				return ($footer.length ? ($footer.outerHeight() || 0) : 0);
			})();

			var submitIsInViewport = (function(){
				var el = $submits.get(0);
				if (!el || !el.getBoundingClientRect) return false;
				var rect = el.getBoundingClientRect();
				var viewportH = window.innerHeight || document.documentElement.clientHeight || 0;
				if (!viewportH) return false;
				var bottomLimit = viewportH - footerH - 8;
				return rect.top < bottomLimit && rect.bottom <= bottomLimit;
			})();

			var actionsAreUsable = (function(){
				var el = $existingActions.get(0);
				if (!el || !el.getBoundingClientRect) return false;
				// Si está oculto por CSS, no sirve.
				if (!$(el).is(':visible')) return false;
				var rect = el.getBoundingClientRect();
				var viewportH = window.innerHeight || document.documentElement.clientHeight || 0;
				if (!viewportH) return false;
				var bottomLimit = viewportH - footerH - 8;
				// Considerar usable solo si el bloque completo queda por encima del footer.
				return rect.top < bottomLimit && rect.bottom <= bottomLimit;
			})();

			var needsFailSafe = (visibleSubmits === 0 || !submitIsInViewport || !actionsAreUsable);
			if (!needsFailSafe) return;

			var idVal = parseInt(($form.find('input[name="id"]').val() || '0'), 10) || 0;
			var isEdit = idVal > 0;
			var cancelHref = isEdit ? ('index.php?page=view_ticket&id=' + idVal) : 'index.php?page=ticket_list';
			var submitLabel = isEdit ? 'Guardar cambios' : 'Guardar Ticket';

			var $actions = $('<div id="ticket-actions-failsafe" class="bg-light border-top ticket-actions" style="position: fixed; left: 0; right: 0; z-index: 2000; padding: .75rem 1rem;"></div>');
			var $inner = $('<div class="container-fluid"></div>');
			var $row = $('<div class="d-flex justify-content-end align-items-center flex-wrap" style="gap: .5rem;"></div>');
			if (!isEdit) {
				$row.append('<button class="btn btn-secondary" type="reset" form="manage_ticket"><i class="fas fa-redo"></i> Limpiar</button>');
			}
			$row.append('<a href="./' + cancelHref + '" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>');
			$row.append('<button class="btn btn-primary" type="submit" form="manage_ticket"><i class="fas fa-save"></i> ' + submitLabel + '</button>');
			$inner.append($row);
			$actions.append($inner);

			$actions.css('bottom', footerH + 'px');
			var extraPad = (footerH + ($actions.outerHeight() || 56) + 12);
			$('.content-wrapper').css('padding-bottom', extraPad + 'px');
			$('body').append($actions);
			document.body.setAttribute('data-ticket-failsafe-injected', '1');
			window.__ticketFailsafeInjected = true;
		} catch (e) {
			// No-op
		}
	}

	// Fallback: si algo rompe jQuery plugins, intenta igualmente cuando el DOM esté listo.
	// (Esto depende de jQuery, pero se ejecuta antes de inicializar plugins.)
	$(function() {
		injectTicketActionsFailSafe();
	});

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

		// Editar Ticket en modal (global): evita 403 por acceso directo a /app/
		$(document)
			.off('click.editTicketModalGlobal', '.edit_ticket_modal')
			.on('click.editTicketModalGlobal', '.edit_ticket_modal', function(e){
				e.preventDefault();
				var id = $(this).data('id');
				id = parseInt(id, 10) || 0;
				if (!id) return;
				uni_modal('Editar Ticket', 'public/ajax/ticket_edit_modal.php?id=' + encodeURIComponent(String(id)), 'modal-lg');
			});

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

					// Inicializar plugins dentro del modal (Select2/Summernote)
					try {
						var $modal = $('#uni_modal');
						// Select2 en modal debe usar dropdownParent para evitar z-index issues
						if ($.fn && typeof $.fn.select2 === 'function') {
							$modal.find('.select2').each(function(){
								var $el = $(this);
								if ($el.data('select2')) return;
								$el.select2({
									placeholder: 'Selecciona aquí',
									width: '100%',
									dropdownParent: $modal
								});
							});
						}

						if ($.fn && typeof $.fn.summernote === 'function') {
							$modal.find('.summernote').each(function(){
								var $el = $(this);
								if ($el.next('.note-editor').length) return;
								$el.summernote({
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
								});

								// Cargar HTML inicial (modo modal) desde data-initial-html
								var raw = $el.attr('data-initial-html');
								if (raw && typeof raw === 'string') {
									try {
										var html = JSON.parse(raw);
										$el.summernote('code', html);
									} catch (e) {}
								}
							});
						}
					} catch (e) {
						// No-op
					}

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

	// Submit delegado: edición de ticket dentro del modal
	$(document).off('submit.ticketModal', '#uni_modal form#manage_ticket').on('submit.ticketModal', '#uni_modal form#manage_ticket', function(e){
		e.preventDefault();
		try {
			$('input').removeClass('border-danger');
			start_load();
			$.ajax({
				url: 'public/ajax/action.php?action=save_ticket',
				data: new FormData(this),
				cache: false,
				contentType: false,
				processData: false,
				method: 'POST',
				type: 'POST',
				success: function(resp){
					end_load();
					if (resp == 1) {
						alert_toast('Cambios guardados correctamente', 'success');
						$('#uni_modal').modal('hide');
						setTimeout(function(){ location.reload(); }, 300);
					} else {
						alert_toast('Error al guardar el ticket', 'error');
					}
				},
				error: function(xhr, status, error){
					end_load();
					console.error('Error AJAX:', error);
					alert_toast('Error de conexión al guardar el ticket', 'error');
				}
			});
		} catch (err) {
			end_load();
		}
	});
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
		// Inicialización de Summernote: si falla, no debe cortar el resto del JS.
		try {
			if ($.fn && typeof $.fn.summernote === 'function') {
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
				});
			}
		} catch (e) {
			// No-op
		}

		// Re-intento del fail-safe (por si el DOM cambia tras init de plugins)
		injectTicketActionsFailSafe();

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

<script>
// Segundo fail-safe (vanilla JS): si por cualquier razón jQuery falla antes, igual intenta renderizar.
(function(){
	try {
		if (document.getElementById('ticket-actions-failsafe')) return;
		var form = document.getElementById('manage_ticket');
		if (!form) return;
		if (form.closest('#uni_modal, #uni_modal_right')) return;

		// No bloquear por existencia de .ticket-actions: si está tapado por footer fijo, igual necesitamos la barra.

		var footer = document.querySelector('.main-footer');
		var footerH = footer ? (footer.getBoundingClientRect().height || 0) : 0;

		var idInput = form.querySelector('input[name="id"]');
		var idVal = idInput ? parseInt((idInput.value || '0'), 10) || 0 : 0;
		var isEdit = idVal > 0;
		var cancelHref = isEdit ? ('index.php?page=view_ticket&id=' + idVal) : 'index.php?page=ticket_list';
		var submitLabel = isEdit ? 'Guardar cambios' : 'Guardar Ticket';

		var actions = document.createElement('div');
		actions.id = 'ticket-actions-failsafe';
		actions.className = 'bg-light border-top ticket-actions';
		actions.style.position = 'fixed';
		actions.style.left = '0';
		actions.style.right = '0';
		actions.style.bottom = footerH + 'px';
		actions.style.zIndex = '2000';
		actions.style.padding = '.75rem 1rem';

		actions.innerHTML =
			'<div class="container-fluid">' +
				'<div class="d-flex justify-content-end align-items-center flex-wrap" style="gap: .5rem;">' +
					(isEdit ? '' : '<button class="btn btn-secondary" type="reset" form="manage_ticket"><i class="fas fa-redo"></i> Limpiar</button>') +
					'<a href="./' + cancelHref + '" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>' +
					'<button class="btn btn-primary" type="submit" form="manage_ticket"><i class="fas fa-save"></i> ' + submitLabel + '</button>' +
				'</div>' +
			'</div>';

		document.body.appendChild(actions);
		document.body.setAttribute('data-ticket-failsafe-injected', '1');
		window.__ticketFailsafeInjected = true;
		var contentWrapper = document.querySelector('.content-wrapper');
		if (contentWrapper) {
			var extraPad = footerH + 56 + 12;
			contentWrapper.style.paddingBottom = extraPad + 'px';
		}
	} catch (e) {
		// No-op
	}
})();
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
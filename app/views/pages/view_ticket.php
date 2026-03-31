<?php define('ACCESS', true); require_once 'config/config.php'; ?>
<?php
$qry = $conn->query("SELECT t.*, 
	COALESCE(CONCAT(c.lastname,', ',c.firstname,' ',c.middlename), t.reporter_name, 'Cliente Publico') as cname, 
	COALESCE(d.name, 'Sin Departamento') as dname,
	t.is_public, t.reporter_email, t.reporter_phone, t.ticket_number, t.issue_type,
	e.name as equipment_name, e.number_inventory,
	COALESCE(CONCAT(ua.firstname,' ',ua.lastname), '') as assigned_name,
	t.assigned_to, t.tracking_token
FROM tickets t 
LEFT JOIN customers c ON c.id = t.customer_id 
LEFT JOIN departments d ON d.id = t.department_id
LEFT JOIN equipments e ON e.id = t.equipment_id
LEFT JOIN users ua ON ua.id = t.assigned_to
WHERE t.id = " . intval($_GET['id']))->fetch_array();

foreach ($qry as $k => $v) { $$k = $v; }

$is_public_ticket = isset($is_public) && $is_public == 1;

// Extraer solo la descripcion libre para tickets publicos (sin metadata con emojis)
$clean_description = $description;
if ($is_public_ticket && !empty($description)) {
	$decoded = html_entity_decode($description);
	// Patron: buscar el texto real de la descripcion despues de "Descripcion de la Falla:" o "Descripcion:"
	if (preg_match('/<p[^>]*>(?:\s*<strong>)?\s*(?:[\x{1F4DD}\x{1F4CB}]\s*)?Descripci[oó]n(?:\s*de la Falla)?:\s*(?:<\/strong>)?\s*<\/p>\s*<p[^>]*>(.*?)<\/p>/uis', $decoded, $m)) {
		$clean_description = htmlentities(strip_tags($m[1]));
	} elseif (preg_match('/\*\*Descripci[oó]n:\*\*\s*\n(.*)/uis', $decoded, $m)) {
		$clean_description = htmlentities(trim($m[1]));
	}
}

// Status helpers
$status_map = [
	0 => ['label' => 'Abierto/Pendiente', 'class' => 'primary', 'icon' => 'folder-open'],
	1 => ['label' => 'En Proceso', 'class' => 'info', 'icon' => 'cog fa-spin'],
	2 => ['label' => 'Finalizado', 'class' => 'success', 'icon' => 'check-circle'],
	3 => ['label' => 'Cerrado', 'class' => 'secondary', 'icon' => 'times-circle'],
];
$st = $status_map[$status] ?? $status_map[0];

// Tiempo transcurrido
$created_ts = strtotime($date_created);
$elapsed = time() - $created_ts;
$elapsed_str = '';
if ($elapsed < 3600) $elapsed_str = floor($elapsed / 60) . ' min';
elseif ($elapsed < 86400) $elapsed_str = floor($elapsed / 3600) . ' h';
else $elapsed_str = floor($elapsed / 86400) . ' d';

// Prioridad
$priority = $priority ?? 'medium';
$priority_map = [
	'low'      => ['label' => 'Baja',     'class' => 'info',    'icon' => 'arrow-down'],
	'medium'   => ['label' => 'Media',     'class' => 'primary', 'icon' => 'minus'],
	'high'     => ['label' => 'Alta',      'class' => 'warning', 'icon' => 'arrow-up'],
	'critical' => ['label' => 'Critica',   'class' => 'danger',  'icon' => 'exclamation-triangle'],
];
$pr = $priority_map[$priority] ?? $priority_map['medium'];

// SLA (semaforo basado en prioridad y tiempo sin resolver)
$sla_hours = ['low' => 72, 'medium' => 48, 'high' => 24, 'critical' => 8];
$sla_limit = ($sla_hours[$priority] ?? 48) * 3600;
$sla_pct = $elapsed / max($sla_limit, 1);
if ($status >= 2) {
	$sla_color = 'success'; $sla_text = 'Resuelto';
} elseif ($sla_pct < 0.5) {
	$sla_color = 'success'; $sla_text = 'En tiempo';
} elseif ($sla_pct < 0.85) {
	$sla_color = 'warning'; $sla_text = 'Proximo a vencer';
} else {
	$sla_color = 'danger'; $sla_text = ($sla_pct >= 1 ? 'SLA Vencido' : 'Critico');
}
?>
<style>
	/* Ticket info */
	.ticket-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 0.5rem 0.5rem 0 0; }
	.ticket-meta-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; margin-bottom: 2px; }
	.ticket-meta-value { font-size: 0.9rem; color: #1f2937; margin-bottom: 0; }
	.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; }
	.separator { border-top: 1px solid #e5e7eb; margin: 1rem 0; }

	/* Priority badges */
	.priority-select { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; border-radius: 4px; padding: 2px 6px; font-size: 0.8rem; cursor: pointer; }
	.priority-select option { color: #333; background: #fff; }
	.sla-indicator { display: inline-flex; align-items: center; gap: 4px; font-size: 0.75rem; padding: 3px 8px; border-radius: 3px; font-weight: 600; }
	.sla-success { background: rgba(16,185,129,0.15); color: #10b981; }
	.sla-warning { background: rgba(245,158,11,0.15); color: #f59e0b; }
	.sla-danger { background: rgba(239,68,68,0.15); color: #ef4444; }

	/* Assignment inline */
	.assign-select { border: 1px solid #d1d5db; border-radius: 4px; padding: 2px 6px; font-size: 0.82rem; max-width: 200px; }

	/* Quick reply dropdown */
	.quick-reply-dropdown { position: relative; display: inline-block; }
	.quick-reply-menu { display: none; position: absolute; bottom: 100%; left: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-height: 250px; overflow-y: auto; min-width: 280px; z-index: 100; }
	.quick-reply-menu.show { display: block; }
	.quick-reply-item { padding: 8px 12px; cursor: pointer; font-size: 0.85rem; border-bottom: 1px solid #f3f4f6; }
	.quick-reply-item:hover { background: #f0f4ff; }
	.quick-reply-item:last-child { border-bottom: none; }
	.quick-reply-cat { font-size: 0.65rem; text-transform: uppercase; color: #9ca3af; font-weight: 600; }

	/* Mention dropdown */
	.mention-dropdown { position: absolute; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); max-height: 180px; overflow-y: auto; min-width: 200px; z-index: 200; display: none; }
	.mention-dropdown.show { display: block; }
	.mention-item { padding: 6px 12px; cursor: pointer; font-size: 0.85rem; }
	.mention-item:hover, .mention-item.active { background: #eef2ff; }

	/* Unified activity stream */
	.activity-stream { position: relative; padding-left: 32px; }
	.activity-stream::before { content: ''; position: absolute; left: 15px; top: 0; bottom: 0; width: 2px; background: #e5e7eb; }
	.activity-entry { position: relative; margin-bottom: 1rem; }
	.activity-entry:last-child { margin-bottom: 0; }
	.activity-dot { position: absolute; left: -25px; top: 4px; width: 18px; height: 18px; border-radius: 50%; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #fff; box-shadow: 0 0 0 2px #e5e7eb; z-index: 1; }
	.dot-created { background: #10b981; }
	.dot-status { background: #3b82f6; }
	.dot-comment { background: #667eea; }
	.dot-attachment { background: #8b5cf6; }
	.dot-internal { background: #f59e0b; }
	.dot-closed { background: #6b7280; }
	.dot-finished { background: #10b981; }

	.activity-meta { font-size: 0.75rem; color: #9ca3af; }
	.activity-author { font-weight: 600; color: #374151; }

	/* Comment bubbles */
	.bubble { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.75rem 1rem; overflow-wrap: break-word; word-break: break-word; }
	.bubble img { max-width: 100%; height: auto; border-radius: 4px; }
	.bubble-own { background: #eef2ff; border-color: #c7d2fe; }
	.bubble-internal { background: #fffbeb; border-color: #fde68a; }

	/* Status event */
	.status-event { display: inline-flex; align-items: center; gap: 0.5rem; background: #f3f4f6; border-radius: 2rem; padding: 0.35rem 0.85rem; font-size: 0.8rem; font-weight: 500; color: #374151; }
	.status-arrow { color: #9ca3af; }

	/* Attachment in stream */
	.stream-attachment { display: inline-flex; align-items: center; gap: 0.5rem; background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 0.5rem; padding: 0.5rem 0.75rem; }
	.stream-att-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; }
	.stream-att-info { font-size: 0.8rem; }
	.stream-att-name { font-weight: 500; color: #374151; }
	.stream-att-size { color: #9ca3af; font-size: 0.7rem; }

	/* Compositor de mensajes */
	.composer-toolbar { display: flex; align-items: center; gap: 2px; padding: 6px 10px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; flex-wrap: wrap; }
	.fmt-btn { background: none; border: none; border-radius: 4px; padding: 4px 7px; cursor: pointer; font-size: 0.85rem; color: #374151; line-height: 1; transition: background 0.15s; }
	.fmt-btn:hover { background: #e5e7eb; }
	.fmt-btn:active { background: #d1d5db; }
	.fmt-sep { width: 1px; height: 18px; background: #d1d5db; margin: 0 4px; }
	.composer-editor { min-height: 90px; max-height: 220px; overflow-y: auto; padding: 10px 12px; font-size: 0.9rem; outline: none; line-height: 1.55; color: #1f2937; }
	.composer-editor:empty::before { content: attr(data-placeholder); color: #9ca3af; pointer-events: none; }
	.composer-editor p { margin: 0 0 4px 0; }
	.staged-files { display: flex; flex-wrap: wrap; gap: 8px; padding: 6px 12px; border-top: 1px solid #f3f4f6; }
	.staged-files:empty { display: none; }
	.staged-chip { display: flex; align-items: center; gap: 6px; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 20px; padding: 3px 10px 3px 6px; font-size: 0.78rem; color: #3730a3; max-width: 180px; }
	.staged-chip .chip-thumb { width: 22px; height: 22px; object-fit: cover; border-radius: 3px; }
	.staged-chip .chip-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px; }
	.staged-chip .chip-remove { cursor: pointer; color: #6366f1; font-size: 0.7rem; margin-left: 2px; }
	.staged-chip.uploading { opacity: 0.6; }
	.composer-actions { display: flex; align-items: center; justify-content: space-between; padding: 7px 10px; border-top: 1px solid #e5e7eb; background: #f8fafc; gap: 8px; }
	.composer-action-btn { background: none; border: none; border-radius: 6px; padding: 5px 8px; cursor: pointer; color: #6b7280; font-size: 0.9rem; transition: color 0.15s, background 0.15s; flex-shrink: 0; }
	.composer-action-btn:hover { color: #374151; background: #e5e7eb; }
	.composer-send-btn { border-radius: 20px; padding: 5px 18px; font-size: 0.82rem; flex-shrink: 0 !important; width: auto !important; display: inline-flex !important; align-items: center; white-space: nowrap; }
	/* Excepción explícita: el botón Enviar nunca debe ser block en mobile */
	.card-footer .composer-send-btn,
	.composer-actions .composer-send-btn { display: inline-flex !important; width: auto !important; margin: 0 !important; }
	.composer-wrap { border: 1px solid #d1d5db; border-radius: 0.5rem; overflow: hidden; background: #fff; transition: border-color 0.2s, box-shadow 0.2s; }
	.composer-wrap:focus-within { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }

	/* Actions */
	.comment-actions { opacity: 0; transition: opacity 0.15s; }
	.activity-entry:hover .comment-actions { opacity: 1; }

	/* Description card for public tickets */
	.public-info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; }
	.public-info-card dt { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; color: #64748b; font-weight: 600; }
	.public-info-card dd { font-size: 0.9rem; color: #1e293b; margin-bottom: 0.75rem; }

	@media (max-width: 768px) {
		.info-grid { grid-template-columns: 1fr 1fr; }
		.activity-stream { padding-left: 28px; }
		.activity-dot { left: -22px; width: 14px; height: 14px; font-size: 7px; }
		.composer-actions .composer-send-btn,
		.composer-actions .btn {
			min-width: auto !important;
			width: auto !important;
			display: inline-flex !important;
			padding: 4px 14px !important;
			font-size: 0.78rem !important;
			margin: 0 !important;
			flex-shrink: 0 !important;
		}
		.composer-actions {
			flex-wrap: nowrap !important;
		}
	}
	@media (max-width: 576px) {
		.info-grid { grid-template-columns: 1fr; }
		.composer-actions .composer-send-btn,
		.composer-actions .btn {
			min-width: auto !important;
			width: auto !important;
			display: inline-flex !important;
			padding: 4px 12px !important;
			font-size: 0.75rem !important;
			margin: 0 !important;
		}
	}
</style>

<div>

	<!-- HEADER -->
	<div class="card shadow-sm mb-3">
		<div class="ticket-header px-4 py-3 d-flex align-items-center justify-content-between flex-wrap">
			<div>
				<h5 class="mb-1">
					<?php echo htmlspecialchars($subject); ?>
				</h5>
				<div style="font-size:0.85rem; opacity:0.85;">
					<?php if (!empty($ticket_number)): ?>
						<?php echo htmlspecialchars($ticket_number); ?> &middot;
					<?php endif; ?>
					<?php echo date('d/m/Y H:i', $created_ts); ?> &middot; hace <?php echo $elapsed_str; ?>
					&middot; <span class="sla-indicator sla-<?php echo $sla_color; ?>"><?php echo $sla_text; ?></span>
				</div>
			</div>
			<div class="d-flex align-items-center mt-2 mt-md-0 flex-wrap" style="gap:0.5rem;">
				<!-- Prioridad -->
				<?php if ($_SESSION['login_type'] != 3): ?>
				<select class="priority-select" id="priority-select" data-id="<?php echo (int)$id; ?>">
					<?php foreach ($priority_map as $pk => $pv): ?>
					<option value="<?php echo $pk; ?>" <?php echo $pk === $priority ? 'selected' : ''; ?>><?php echo $pv['label']; ?></option>
					<?php endforeach; ?>
				</select>
				<?php else: ?>
				<span class="badge badge-<?php echo $pr['class']; ?>" style="font-size:0.8rem; padding:0.3em 0.6em;">
					<i class="fas fa-<?php echo $pr['icon']; ?>"></i> <?php echo $pr['label']; ?>
				</span>
				<?php endif; ?>

				<span class="badge badge-<?php echo $st['class']; ?>" style="font-size:0.85rem; padding:0.4em 0.8em;">
					<i class="fas fa-<?php echo $st['icon']; ?>"></i> <?php echo $st['label']; ?>
				</span>
				<?php if ($is_public_ticket): ?>
					<span class="badge badge-warning" style="font-size:0.8rem; padding:0.4em 0.7em;">
						<i class="fas fa-qrcode"></i> QR
					</span>
				<?php endif; ?>
				<?php if ($_SESSION['login_type'] != 3): ?>
					<button class="btn btn-sm btn-light update_status" data-id="<?php echo $id; ?>">
						<i class="fas fa-exchange-alt"></i> Cambiar Estado
					</button>
					<a href="public/ajax/action.php?action=generate_ticket_pdf&ticket_id=<?php echo (int)$id; ?>" target="_blank" class="btn btn-sm btn-light" title="Imprimir / PDF">
						<i class="fas fa-print"></i>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="card-body">
			<!-- Datos del ticket -->
			<div class="info-grid">
				<?php if (!$is_public_ticket): ?>
				<div>
					<div class="ticket-meta-label">Cliente</div>
					<p class="ticket-meta-value"><?php echo htmlspecialchars($cname); ?></p>
				</div>
				<?php endif; ?>
				<div>
					<div class="ticket-meta-label">Departamento</div>
					<p class="ticket-meta-value"><?php echo htmlspecialchars($dname); ?></p>
				</div>
				<?php if ($_SESSION['login_type'] != 3): ?>
				<div>
					<div class="ticket-meta-label">Tecnico Asignado</div>
					<select class="assign-select form-control form-control-sm" id="assign-select" data-id="<?php echo (int)$id; ?>">
						<option value="0">-- Sin asignar --</option>
					</select>
				</div>
				<?php elseif (!empty($assigned_name)): ?>
				<div>
					<div class="ticket-meta-label">Tecnico Asignado</div>
					<p class="ticket-meta-value"><?php echo htmlspecialchars($assigned_name); ?></p>
				</div>
				<?php endif; ?>
				<?php if ($is_public_ticket): ?>
				<div>
					<div class="ticket-meta-label">Reportado por</div>
					<p class="ticket-meta-value"><?php echo htmlspecialchars($cname); ?></p>
				</div>
				<?php if (!empty($reporter_email)): ?>
				<div>
					<div class="ticket-meta-label">Email</div>
					<p class="ticket-meta-value"><?php echo htmlspecialchars($reporter_email); ?></p>
				</div>
				<?php endif; ?>
				<?php if (!empty($reporter_phone)): ?>
				<div>
					<div class="ticket-meta-label">Telefono</div>
					<p class="ticket-meta-value"><?php echo htmlspecialchars($reporter_phone); ?></p>
				</div>
				<?php endif; ?>
				<?php if (!empty($equipment_name)): ?>
				<div>
					<div class="ticket-meta-label">Equipo</div>
					<p class="ticket-meta-value"><?php echo htmlspecialchars($equipment_name); ?> <?php if (!empty($number_inventory)) echo '#' . htmlspecialchars($number_inventory); ?></p>
				</div>
				<?php endif; ?>
				<?php if (!empty($issue_type)): ?>
				<div>
					<div class="ticket-meta-label">Tipo de Falla</div>
					<p class="ticket-meta-value"><?php echo htmlspecialchars($issue_type); ?></p>
				</div>
				<?php endif; ?>
				<?php endif; ?>
			</div>

			<div class="separator"></div>

			<!-- Descripcion -->
			<div>
				<div class="ticket-meta-label mb-1">Descripcion</div>
				<div class="bg-light p-3 rounded" style="border:1px solid #e5e7eb;">
					<?php echo html_entity_decode($clean_description); ?>
				</div>
			</div>

			<!-- Acciones -->
			<div class="mt-3 d-flex flex-wrap" style="gap:0.5rem;">
				<a href="./index.php?page=edit_ticket&id=<?php echo (int)$id; ?>" class="btn btn-outline-primary btn-sm">
					<i class="fas fa-edit"></i> Editar Ticket
				</a>
				<a href="./index.php?page=ticket_list" class="btn btn-outline-secondary btn-sm">
					<i class="fas fa-arrow-left"></i> Volver a la Lista
				</a>
				<?php if (!empty($tracking_token) && $is_public_ticket): ?>
				<button class="btn btn-outline-info btn-sm" onclick="copyTrackingUrl()" title="Copiar enlace de seguimiento publico">
					<i class="fas fa-link"></i> Enlace de Seguimiento
				</button>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- ACTIVITY STREAM (unified timeline: status + comments + attachments) -->
	<div class="card shadow-sm">
		<div class="card-header d-flex align-items-center justify-content-between" style="background: #f8fafc; border-bottom: 1px solid #e5e7eb;">
			<h5 class="mb-0" style="font-size:1rem; font-weight:600; color:#374151;">
				<i class="fas fa-stream text-primary"></i> Actividad del Ticket
			</h5>
		</div>
		<div class="card-body p-0" style="max-height: 500px; overflow-y: auto;" id="activity-scroll">
			<div style="padding: 1rem 1.25rem;">
			<div class="activity-stream" id="activity-stream">
				<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Cargando actividad...</div>
			</div>
			</div>
		</div>

		<!-- Compositor de mensajes -->
		<div class="card-footer p-3" style="background:#fff; border-top: 2px solid #e5e7eb;">
			<form action="" id="manage-comment">
				<input type="hidden" name="id" value="">
				<input type="hidden" name="ticket_id" value="<?php echo $id; ?>">
				<input type="file" id="attachment-input" accept="image/*,.pdf,.doc,.docx,.xlsx,.xls" style="display:none" multiple>

				<?php if ($_SESSION['login_type'] != 3): ?>
				<div class="d-flex align-items-center mb-2" style="gap:0.6rem;">
					<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" id="is_internal_check" name="is_internal" value="1">
						<label class="custom-control-label small text-muted" for="is_internal_check" style="font-size:0.8rem;">
							<i class="fas fa-lock"></i> Nota interna
						</label>
					</div>
					<div class="quick-reply-dropdown">
						<button type="button" class="btn btn-outline-secondary btn-sm" id="quick-reply-btn" style="font-size:0.78rem; padding:2px 9px;">
							<i class="fas fa-bolt"></i> Rapida
						</button>
						<div class="quick-reply-menu" id="quick-reply-menu"></div>
					</div>
				</div>
				<?php endif; ?>

				<!-- Compositor estilo mensajeria -->
				<div class="composer-wrap" id="composer-wrap">
					<!-- Barra de formato -->
					<div class="composer-toolbar" id="fmt-toolbar">
						<button type="button" class="fmt-btn" onmousedown="event.preventDefault();document.execCommand('bold')" title="Negrita"><b>B</b></button>
						<button type="button" class="fmt-btn" onmousedown="event.preventDefault();document.execCommand('italic')" title="Cursiva"><i>I</i></button>
						<button type="button" class="fmt-btn" onmousedown="event.preventDefault();document.execCommand('underline')" title="Subrayado"><u>U</u></button>
						<span class="fmt-sep"></span>
						<button type="button" class="fmt-btn" onmousedown="event.preventDefault();document.execCommand('insertUnorderedList')" title="Lista"><i class="fas fa-list-ul" style="font-size:0.75rem;"></i></button>
						<button type="button" class="fmt-btn" onmousedown="event.preventDefault();document.execCommand('insertOrderedList')" title="Lista numerada"><i class="fas fa-list-ol" style="font-size:0.75rem;"></i></button>
						<span class="fmt-sep"></span>
						<button type="button" class="fmt-btn" onmousedown="event.preventDefault();insertImageToEditor()" title="Insertar imagen"><i class="fas fa-image" style="font-size:0.75rem;"></i></button>
						<span class="fmt-sep"></span>
						<button type="button" class="fmt-btn" onmousedown="event.preventDefault();document.execCommand('undo')" title="Deshacer"><i class="fas fa-undo" style="font-size:0.72rem;"></i></button>
						<button type="button" class="fmt-btn" onmousedown="event.preventDefault();document.execCommand('redo')" title="Rehacer"><i class="fas fa-redo" style="font-size:0.72rem;"></i></button>
					</div>

					<!-- Area de texto -->
					<div id="ticket_comment"
						contenteditable="true"
						class="composer-editor"
						data-placeholder="Escribe un comentario... (usa @ para mencionar)"
					></div>
					<div class="mention-dropdown" id="mention-dropdown"></div>

					<!-- Archivos en cola -->
					<div class="staged-files" id="staged-files"></div>

					<!-- Barra inferior -->
					<div class="composer-actions">
						<div style="display:flex; gap:4px;">
							<button type="button" class="composer-action-btn" onclick="$('#attachment-input').click()" title="Adjuntar archivo">
								<i class="fas fa-paperclip"></i>
							</button>
							<button type="button" class="composer-action-btn" onclick="triggerInlineImagePick()" title="Insertar imagen en el texto">
								<i class="fas fa-image"></i>
							</button>
						</div>
						<button type="submit" class="btn btn-primary btn-sm composer-send-btn" style="display:inline-flex !important;width:auto !important;flex-shrink:0 !important;align-items:center !important;white-space:nowrap !important;padding:5px 18px !important;margin:0 !important;">
							<i class="fas fa-paper-plane"></i> Enviar
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Lightbox visor de imágenes -->
<div id="img-lightbox" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(0,0,0,0.88); align-items:center; justify-content:center; cursor:zoom-out;" role="dialog" aria-modal="true" aria-label="Visor de imagen">
	<button id="img-lightbox-close" title="Cerrar (ESC)" style="position:absolute; top:12px; right:20px; background:rgba(255,255,255,0.12); border:none; color:#fff; font-size:2rem; cursor:pointer; z-index:100000; line-height:1; border-radius:50%; width:44px; height:44px; display:flex; align-items:center; justify-content:center;">&times;</button>
	<img id="img-lightbox-img" src="" alt="Vista ampliada" style="max-width:92vw; max-height:90vh; object-fit:contain; border-radius:6px; box-shadow:0 8px 40px rgba(0,0,0,0.6); cursor:default;" onclick="event.stopPropagation()">
</div>

<script>
var ticketId = <?php echo (int)$id; ?>;
var currentUserId = <?php echo (int)$_SESSION['login_id']; ?>;
var currentUserType = <?php echo (int)$_SESSION['login_type']; ?>;
var ticketCreatedAt = '<?php echo date('Y-m-d H:i:s', $created_ts); ?>';
var currentAssignedTo = <?php echo (int)($assigned_to ?? 0); ?>;
var trackingToken = '<?php echo htmlspecialchars($tracking_token ?? ''); ?>';
var statusLabels = {0:'Abierto/Pendiente', 1:'En Proceso', 2:'Finalizado', 3:'Cerrado'};
var statusDotClass = {0:'dot-created', 1:'dot-status', 2:'dot-finished', 3:'dot-closed'};
var statusIcons = {0:'fas fa-folder-open', 1:'fas fa-cog', 2:'fas fa-check', 3:'fas fa-times'};

$(function() {
	// Editor nativo contenteditable
	$('#ticket_comment').on('keydown', function(e) {
		handleMentionKey(e);
	});
	// Pegar: limpiar HTML externo pero conservar imagenes inline
	$('#ticket_comment').on('paste', function(e) {
		var cd = e.originalEvent.clipboardData;
		if (!cd) return;
		var items = cd.items;
		for (var i = 0; i < items.length; i++) {
			if (items[i].type.indexOf('image') !== -1) {
				e.preventDefault();
				var blob = items[i].getAsFile();
				var reader = new FileReader();
				reader.onload = function(ev) {
					document.execCommand('insertImage', false, ev.target.result);
				};
				reader.readAsDataURL(blob);
				return;
			}
		}
		if (cd.types.indexOf('text/html') !== -1) {
			e.preventDefault();
			var html = cd.getData('text/html');
			var clean = $('<div>').html(html).find('img').length
				? html
				: cd.getData('text/plain');
			document.execCommand('insertHTML', false, clean);
		}
	});

	loadActivityStream();
	initAttachInput();
	if (currentUserType != 3) {
		loadTechnicians();
		loadQuickReplies();
	}
});

// Build unified activity stream
function loadActivityStream() {
	// Load all data sources in parallel
	var events = [];
	var doneCount = 0, totalRequests = 3;

	function checkDone() {
		doneCount++;
		if (doneCount < totalRequests) return;
		// Sort all events chronologically
		events.sort(function(a, b) { return new Date(a.date) - new Date(b.date); });
		renderStream(events);
	}

	// 1) Creation event
	events.push({
		type: 'created', date: ticketCreatedAt,
		data: { label: 'Ticket creado' }
	});

	// 2) Status history
	$.getJSON('public/ajax/action.php?action=get_ticket_timeline&ticket_id=' + ticketId, function(data) {
		if (data && data.length) {
			data.forEach(function(item) {
				events.push({ type: 'status', date: item.created_at, data: item });
			});
		}
	}).always(checkDone);

	// 3) Comments
	$.getJSON('public/ajax/action.php?action=get_comments&ticket_id=' + ticketId, function(data) {
		if (data && data.length) {
			data.forEach(function(c) {
				events.push({ type: 'comment', date: c.date_created, data: c });
			});
		}
	}).always(checkDone);

	// 4) Attachments
	$.getJSON('public/ajax/action.php?action=get_ticket_attachments&ticket_id=' + ticketId, function(data) {
		if (data && data.length) {
			data.forEach(function(att) {
				events.push({ type: 'attachment', date: att.created_at, data: att });
			});
		}
	}).always(checkDone);
}

function renderStream(events) {
	var $stream = $('#activity-stream');
	$stream.empty();

	if (!events.length) {
		$stream.html('<div class="text-center text-muted py-4">Sin actividad registrada</div>');
		return;
	}

	events.forEach(function(ev) {
		var html = '';
		switch (ev.type) {
			case 'created': html = renderCreatedEvent(ev); break;
			case 'status': html = renderStatusEvent(ev); break;
			case 'comment': html = renderCommentEvent(ev); break;
			case 'attachment': html = renderAttachmentEvent(ev); break;
		}
		if (html) $stream.append(html);
	});

	// Scroll to bottom
	var $scroll = $('#activity-scroll');
	$scroll.scrollTop($scroll[0].scrollHeight);
}

function formatDate(d) {
	if (!d) return '';
	var dt = new Date(d);
	var dd = String(dt.getDate()).padStart(2,'0');
	var mm = String(dt.getMonth()+1).padStart(2,'0');
	var yy = dt.getFullYear();
	var hh = String(dt.getHours()).padStart(2,'0');
	var mi = String(dt.getMinutes()).padStart(2,'0');
	return dd+'/'+mm+'/'+yy+' '+hh+':'+mi;
}

function renderCreatedEvent(ev) {
	return '<div class="activity-entry">' +
		'<div class="activity-dot dot-created"><i class="fas fa-plus" style="font-size:8px"></i></div>' +
		'<div class="status-event"><i class="fas fa-ticket-alt text-success"></i> Ticket creado</div>' +
		'<div class="activity-meta mt-1">' + formatDate(ev.date) + '</div>' +
		'</div>';
}

function renderStatusEvent(ev) {
	var d = ev.data;
	var oldLabel = d.old_status !== null ? (statusLabels[d.old_status] || 'Estado '+d.old_status) : 'Creado';
	var newLabel = statusLabels[d.new_status] || ('Estado '+d.new_status);
	var dotCls = statusDotClass[d.new_status] || 'dot-status';
	var icon = statusIcons[d.new_status] || 'fas fa-exchange-alt';
	var html = '<div class="activity-entry">' +
		'<div class="activity-dot ' + dotCls + '"><i class="' + icon + '" style="font-size:8px"></i></div>' +
		'<div class="status-event"><span>' + oldLabel + '</span> <i class="fas fa-long-arrow-alt-right status-arrow"></i> <span>' + newLabel + '</span></div>' +
		'<div class="activity-meta mt-1">' + escapeHtml(d.changed_by_name || 'Sistema') + ' &middot; ' + formatDate(ev.date) + '</div>';
	if (d.comment) html += '<div class="small text-secondary mt-1" style="padding-left:2px">' + escapeHtml(d.comment) + '</div>';
	html += '</div>';
	return html;
}

function renderCommentEvent(ev) {
	var c = ev.data;
	var isOwn = (c.user_type == currentUserType && c.user_id == currentUserId);
	var isInternal = c.is_internal == 1;
	// Hide internal notes from customers
	if (isInternal && currentUserType == 3) return '';
	var dotCls = isInternal ? 'dot-internal' : 'dot-comment';
	var bubbleCls = isInternal ? 'bubble-internal' : (isOwn ? 'bubble-own' : '');
	var html = '<div class="activity-entry">' +
		'<div class="activity-dot ' + dotCls + '"><i class="fas fa-comment" style="font-size:8px"></i></div>' +
		'<div class="d-flex align-items-start justify-content-between">' +
		'<div>' +
		'<span class="activity-author">' + escapeHtml(c.user_name || 'Usuario') + '</span>';
	if (isInternal) html += ' <span class="badge badge-warning" style="font-size:0.6rem;">Nota interna</span>';
	html += '<span class="activity-meta ml-2">' + formatDate(ev.date) + '</span></div>';
	if (isOwn) {
		html += '<div class="comment-actions">' +
			'<button class="btn btn-tool btn-sm edit_comment" data-id="' + c.id + '" title="Editar"><i class="fas fa-pencil-alt text-muted"></i></button>' +
			'<button class="btn btn-tool btn-sm delete_comment" data-id="' + c.id + '" title="Eliminar"><i class="fas fa-trash text-muted"></i></button>' +
			'</div>';
	}
	html += '</div>' +
		'<div class="bubble ' + bubbleCls + ' mt-1">' + c.comment_html + '</div>' +
		'</div>';
	return html;
}

function renderAttachmentEvent(ev) {
	var a = ev.data;
	var isImage = a.file_type && a.file_type.indexOf('image') === 0;
	var sizeKB = a.file_size ? Math.round(a.file_size / 1024) : '';
	var html = '<div class="activity-entry">' +
		'<div class="activity-dot dot-attachment"><i class="fas fa-paperclip" style="font-size:8px"></i></div>' +
		'<div class="activity-meta"><span class="activity-author">' + escapeHtml(a.uploaded_by_name || 'Usuario') + '</span> adjunto un archivo &middot; ' + formatDate(ev.date) + '</div>' +
		'<div class="stream-attachment mt-1">';
	if (isImage) {
		html += '<a href="' + a.file_path + '" target="_blank"><img src="' + a.file_path + '" class="stream-att-thumb" alt=""></a>';
	} else {
		html += '<a href="' + a.file_path + '" target="_blank" class="d-flex align-items-center justify-content-center stream-att-thumb bg-light"><i class="fas fa-file-pdf fa-lg text-danger"></i></a>';
	}
	html += '<div class="stream-att-info ml-2"><div class="stream-att-name">' + escapeHtml(a.file_name) + '</div>';
	if (sizeKB) html += '<div class="stream-att-size">' + sizeKB + ' KB</div>';
	html += '</div>';
	<?php if ($_SESSION['login_type'] != 3): ?>
	html += '<button class="btn btn-sm btn-outline-danger ml-auto delete-att-btn" data-id="' + a.id + '" title="Eliminar"><i class="fas fa-times"></i></button>';
	<?php endif; ?>
	html += '</div></div>';
	return html;
}

function escapeHtml(str) {
	if (!str) return '';
	var d = document.createElement('div');
	d.appendChild(document.createTextNode(str));
	return d.innerHTML;
}

// Imagen inline desde toolbar
var inlineImageInput = null;
function insertImageToEditor() {
	if (!inlineImageInput) {
		inlineImageInput = document.createElement('input');
		inlineImageInput.type = 'file';
		inlineImageInput.accept = 'image/*';
		inlineImageInput.addEventListener('change', function() {
			if (!this.files.length) return;
			var reader = new FileReader();
			var file = this.files[0];
			reader.onload = function(ev) {
				$('#ticket_comment').focus();
				document.execCommand('insertImage', false, ev.target.result);
			};
			reader.readAsDataURL(file);
			this.value = '';
		});
	}
	inlineImageInput.click();
}
function triggerInlineImagePick() { insertImageToEditor(); }

// Staged files (adjuntos pendientes antes de enviar)
var stagedFiles = [];
function initAttachInput() {
	$('#attachment-input').on('change', function() {
		for (var i = 0; i < this.files.length; i++) stageFile(this.files[i]);
		this.value = '';
	});
	// Drag & drop sobre el compositor
	$('#composer-wrap').on('dragover', function(e) {
		e.preventDefault();
		$(this).css('border-color','#667eea');
	}).on('dragleave drop', function(e) {
		$(this).css('border-color','');
		if (e.type === 'drop') {
			e.preventDefault();
			var files = e.originalEvent.dataTransfer.files;
			for (var i = 0; i < files.length; i++) stageFile(files[i]);
		}
	});
}

function stageFile(file) {
	var id = 'sf_' + Date.now() + '_' + Math.random().toString(36).substr(2,5);
	stagedFiles.push({ id: id, file: file });
	var isImg = file.type.indexOf('image') === 0;
	var chipHtml = '<div class="staged-chip" id="' + id + '">';
	if (isImg) {
		var url = URL.createObjectURL(file);
		chipHtml += '<img class="chip-thumb" src="' + url + '">';
	} else {
		chipHtml += '<i class="fas fa-file-alt" style="font-size:1rem;color:#6366f1;"></i>';
	}
	chipHtml += '<span class="chip-name" title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + '</span>';
	chipHtml += '<span class="chip-remove" data-sid="' + id + '"><i class="fas fa-times"></i></span></div>';
	$('#staged-files').append(chipHtml);
}
$(document).on('click', '.chip-remove', function() {
	var sid = $(this).data('sid');
	stagedFiles = stagedFiles.filter(function(f){ return f.id !== sid; });
	$('#' + sid).remove();
});

function uploadFile(file) {
	var fd = new FormData();
	fd.append('attachment', file);
	fd.append('ticket_id', ticketId);
	$.ajax({
		url: 'public/ajax/action.php?action=upload_ticket_attachment',
		data: fd, cache: false, contentType: false, processData: false, method: 'POST',
		success: function(resp) {
			try { resp = typeof resp === 'string' ? JSON.parse(resp) : resp; } catch(e) {}
			if (resp.status == 1) { alert_toast('Archivo subido', 'success'); loadActivityStream(); }
			else alert_toast(resp.msg || 'Error al subir archivo', 'error');
		},
		error: function() { alert_toast('Error de conexion', 'error'); }
	});
}

// Delegated events for dynamic content
$(document).on('click', '.delete-att-btn', function() {
	_conf("Eliminar este adjunto?", "confirmDeleteAttachment", [$(this).data('id')]);
});
function confirmDeleteAttachment(id) {
	$.ajax({
		url: 'public/ajax/action.php?action=delete_ticket_attachment',
		method: 'POST', data: { id: id },
		success: function(resp) {
			try { resp = typeof resp === 'string' ? JSON.parse(resp) : resp; } catch(e) {}
			if (resp.status == 1) { alert_toast('Adjunto eliminado', 'success'); loadActivityStream(); }
			else alert_toast(resp.msg || 'Error', 'error');
		}
	});
}

$(document).on('click', '.edit_comment', function() {
	uni_modal("Editar Comentario", "modals/manage_comment.php?id=" + $(this).data('id'));
});
$(document).on('click', '.delete_comment', function() {
	_conf("Deseas eliminar este comentario?", "delete_comment", [$(this).data('id')]);
});
$('.update_status').click(function() {
	uni_modal("Actualizar estado del ticket", "modals/manage_ticket.php?id=" + $(this).data('id'));
});

function delete_comment(id) {
	start_load();
	$.ajax({
		url: 'public/ajax/action.php?action=delete_comment', method: 'POST', data: { id: id },
		success: function(resp) {
			if (resp == 1) { alert_toast("Comentario eliminado", 'success'); setTimeout(function() { loadActivityStream(); end_load(); }, 500); }
		}
	});
}

$('#manage-comment').submit(function(e) {
	e.preventDefault();
	var $editor = $('#ticket_comment');
	var commentHtml = $editor.html().trim();
	if (!commentHtml || commentHtml === '<br>') {
		if (!stagedFiles.length) { alert_toast('Escribe un comentario o adjunta un archivo', 'warning'); return; }
	}
	start_load();

	// Subir archivos en cola primero, luego guardar comentario
	var uploads = stagedFiles.slice();
	stagedFiles = []; $('#staged-files').empty();

	function saveComment() {
		var fd = new FormData();
		fd.append('ticket_id', ticketId);
		fd.append('comment', commentHtml);
		fd.append('id', $('#manage-comment input[name=id]').val());
		var isInternal = $('#is_internal_check').is(':checked') ? 1 : 0;
		fd.append('is_internal', isInternal);
		if (!commentHtml || commentHtml === '<br>') { end_load(); loadActivityStream(); return; }
		$.ajax({
			url: 'public/ajax/action.php?action=save_comment',
			data: fd, cache: false, contentType: false, processData: false, method: 'POST',
			success: function(resp) {
				if (resp == 1) {
					alert_toast('Comentario guardado', 'success');
					$editor.empty();
					$('#manage-comment input[name=id]').val('');
					setTimeout(function() { loadActivityStream(); end_load(); }, 500);
				} else { end_load(); }
			},
			error: function() { end_load(); alert_toast('Error al guardar', 'error'); }
		});
	}

	if (!uploads.length) { saveComment(); return; }
	var done = 0;
	uploads.forEach(function(sf) {
		var fd2 = new FormData();
		fd2.append('attachment', sf.file);
		fd2.append('ticket_id', ticketId);
		$.ajax({
			url: 'public/ajax/action.php?action=upload_ticket_attachment',
			data: fd2, cache: false, contentType: false, processData: false, method: 'POST',
			complete: function() { done++; if (done === uploads.length) saveComment(); }
		});
	});
});

// ========== PRIORIDAD ==========
$('#priority-select').on('change', function() {
	var priority = $(this).val();
	$.ajax({
		url: 'public/ajax/action.php?action=change_priority',
		method: 'POST', data: { ticket_id: ticketId, priority: priority },
		success: function(resp) {
			try { resp = typeof resp === 'string' ? JSON.parse(resp) : resp; } catch(e) {}
			if (resp.status == 1) alert_toast('Prioridad actualizada', 'success');
			else alert_toast(resp.msg || 'Error', 'error');
		}
	});
});

// ========== ASIGNACION DE TECNICO ==========
function loadTechnicians() {
	$.getJSON('public/ajax/action.php?action=get_technicians', function(data) {
		var $sel = $('#assign-select');
		if (!$sel.length) return;
		if (data && data.length) {
			data.forEach(function(t) {
				$sel.append('<option value="' + t.id + '"' + (t.id == currentAssignedTo ? ' selected' : '') + '>' + escapeHtml(t.name) + '</option>');
			});
		}
	});
}
$('#assign-select').on('change', function() {
	var val = $(this).val();
	$.ajax({
		url: 'public/ajax/action.php?action=assign_ticket',
		method: 'POST', data: { ticket_id: ticketId, assigned_to: val },
		success: function(resp) {
			try { resp = typeof resp === 'string' ? JSON.parse(resp) : resp; } catch(e) {}
			if (resp.status == 1) { alert_toast('Tecnico asignado', 'success'); loadActivityStream(); }
			else alert_toast(resp.msg || 'Error', 'error');
		}
	});
});

// ========== RESPUESTAS RAPIDAS ==========
function loadQuickReplies() {
	$.getJSON('public/ajax/action.php?action=get_quick_replies', function(data) {
		var $menu = $('#quick-reply-menu');
		if (!$menu.length || !data || !data.length) return;
		var html = '';
		data.forEach(function(r) {
			html += '<div class="quick-reply-item" data-content="' + escapeHtml(r.content).replace(/"/g, '&quot;') + '">';
			html += '<div class="quick-reply-cat">' + escapeHtml(r.category) + '</div>';
			html += '<div>' + escapeHtml(r.title) + '</div>';
			html += '</div>';
		});
		$menu.html(html);
	});
}
$(document).on('click', '#quick-reply-btn', function(e) {
	e.stopPropagation();
	$('#quick-reply-menu').toggleClass('show');
});
$(document).on('click', '.quick-reply-item', function() {
	var content = $(this).data('content');
	$('#ticket_comment').html(content).focus();
	$('#quick-reply-menu').removeClass('show');
});
$(document).on('click', function(e) {
	if (!$(e.target).closest('.quick-reply-dropdown').length) {
		$('#quick-reply-menu').removeClass('show');
	}
});

// ========== @MENCIONES ==========
var mentionActive = false, mentionQuery = '', mentionStartPos = 0, mentionUsers = [];
function handleMentionKey(e) {
	if (e.key === '@') {
		mentionActive = true;
		mentionQuery = '';
		mentionStartPos = window.getSelection().focusOffset;
	} else if (mentionActive) {
		if (e.key === 'Escape' || e.key === ' ') {
			closeMentionDropdown();
		} else if (e.key === 'Backspace') {
			mentionQuery = mentionQuery.slice(0, -1);
			if (mentionQuery.length === 0) closeMentionDropdown();
			else searchMentionUsers();
		} else if (e.key.length === 1 && /[a-zA-ZáéíóúñÁÉÍÓÚÑ]/.test(e.key)) {
			mentionQuery += e.key;
			searchMentionUsers();
		} else if (e.key === 'Enter' && mentionUsers.length > 0) {
			e.preventDefault();
			insertMention(mentionUsers[0]);
		}
	}
}
function searchMentionUsers() {
	if (mentionQuery.length < 1) return;
	$.getJSON('public/ajax/action.php?action=search_users&q=' + encodeURIComponent(mentionQuery), function(data) {
		mentionUsers = data || [];
		renderMentionDropdown();
	});
}
function renderMentionDropdown() {
	var $dd = $('#mention-dropdown');
	if (!mentionUsers.length) { $dd.removeClass('show').empty(); return; }
	var html = '';
	mentionUsers.forEach(function(u) {
		html += '<div class="mention-item" data-id="' + u.id + '" data-name="' + escapeHtml(u.name) + '" data-type="' + u.type + '">' + escapeHtml(u.name) + ' <small style="color:#9ca3af">(' + u.type + ')</small></div>';
	});
	$dd.html(html).addClass('show');
}
function closeMentionDropdown() {
	mentionActive = false;
	mentionQuery = '';
	$('#mention-dropdown').removeClass('show').empty();
}
$(document).on('click', '.mention-item', function() {
	insertMention({ id: $(this).data('id'), name: $(this).data('name'), type: $(this).data('type') });
});
function insertMention(user) {
	var mentionTag = '<span class="badge badge-info mention-tag" contenteditable="false" data-user-id="' + user.id + '" style="font-size:0.82rem;">@' + escapeHtml(user.name) + '</span>&nbsp;';
	var $note = $('#ticket_comment');
	var html = $note.html();
	var regex = new RegExp('@' + mentionQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '(?=[^a-zA-Z]|$)');
	if (regex.test(html)) {
		html = html.replace(regex, mentionTag);
	} else {
		html += mentionTag;
	}
	$note.html(html);
	// Mover cursor al final
	var range = document.createRange();
	range.selectNodeContents($note[0]);
	range.collapse(false);
	var sel = window.getSelection();
	sel.removeAllRanges();
	sel.addRange(range);
	closeMentionDropdown();
}

// ========== LIGHTBOX VISOR DE IMÁGENES ==========
(function() {
	var $box = $('#img-lightbox');
	var $img = $('#img-lightbox-img');
	var $closeBtn = $('#img-lightbox-close');

	function openLightbox(src) {
		$img.attr('src', src);
		$box.css('display', 'flex');
		$('body').css('overflow', 'hidden');
		setTimeout(function() { $closeBtn.focus(); }, 50);
	}
	function closeLightbox() {
		$box.css('display', 'none');
		$img.attr('src', '');
		$('body').css('overflow', '');
	}

	// Clic en el fondo oscuro: cerrar
	$box.on('click', function(e) {
		if (e.target === this) closeLightbox();
	});
	// Botón X: cerrar
	$closeBtn.on('click', function(e) {
		e.stopPropagation();
		closeLightbox();
	});
	// Tecla ESC: cerrar
	$(document).on('keydown.lightbox', function(e) {
		if ((e.key === 'Escape' || e.keyCode === 27) && $box.is(':visible')) {
			closeLightbox();
		}
	});

	// Clic en enlaces de imágenes adjuntas del stream de actividad
	$(document).on('click', '#activity-stream a[href]', function(e) {
		var href = $(this).attr('href') || '';
		if (/\.(jpe?g|png|gif|webp|bmp|svg)(\?.*)?$/i.test(href)) {
			e.preventDefault();
			openLightbox(href);
		}
	});

	// Clic en imágenes inline dentro de comentarios (contenteditable)
	$(document).on('click', '.bubble img', function(e) {
		e.stopPropagation();
		var src = $(this).attr('src');
		if (src) openLightbox(src);
	});

	// Cursor zoom-in para imágenes inline
	$(document).on('mouseenter', '.bubble img', function() {
		$(this).css('cursor', 'zoom-in');
	});
})();

// ========== TRACKING URL ==========
function copyTrackingUrl() {
	if (!trackingToken) return;
	var baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname.replace(/index\.php.*/, '');
	var url = baseUrl + 'public/track.php?token=' + trackingToken;
	if (navigator.clipboard) {
		navigator.clipboard.writeText(url).then(function() { alert_toast('Enlace copiado al portapapeles', 'success'); });
	} else {
		var tmp = document.createElement('textarea');
		tmp.value = url; document.body.appendChild(tmp); tmp.select(); document.execCommand('copy'); document.body.removeChild(tmp);
		alert_toast('Enlace copiado al portapapeles', 'success');
	}
}
</script>

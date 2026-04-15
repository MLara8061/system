<?php
// Configurar zona horaria de México
date_default_timezone_set('America/Cancun');

require_once 'config/config.php';

// === OBTENER NOMBRE DEL USUARIO LOGUEADO ===
$session_first = isset($_SESSION['login_firstname']) ? $_SESSION['login_firstname'] : '';
$session_middle = isset($_SESSION['login_middlename']) ? $_SESSION['login_middlename'] : '';
$session_last = isset($_SESSION['login_lastname']) ? $_SESSION['login_lastname'] : '';
$session_username = isset($_SESSION['login_username']) ? $_SESSION['login_username'] : '';
$current_user_name = trim(implode(' ', array_filter([$session_first, $session_middle, $session_last])));
if ($current_user_name === '') {
    $current_user_name = $session_username;
}
$current_user_name = $current_user_name ?: 'No registrado';

// === DATOS DE EMPRESA (dinámicos desde BD) ===
$root_path = defined('ROOT') ? ROOT : realpath(__DIR__ . '/../../../..');
require_once $root_path . '/app/helpers/company_config_helper.php';

$_branch_id = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
$_company_cfg = get_company_config($conn, $_branch_id);
$_company_logo_url = get_company_logo_url($conn, $_branch_id);

$company_info = [
    'company_name' => $_company_cfg['company_name'],
    'address_line_1' => $_company_cfg['address_line_1'],
    'address_line_2' => $_company_cfg['address_line_2'],
    'city_state_zip' => $_company_cfg['city_state_zip'],
    'phone_number' => $_company_cfg['phone_number'],
];

$orden_mto = generate_sequential_folio($conn, $_branch_id, 'report');
$fecha_reporte = date('d/m/Y');
// El nombre del ingeniero es el mismo usuario logueado que genera el reporte
$ingeniero_nombre = $current_user_name;

// === CONSULTAS ===
$equip_where = function_exists('branch_sql') ? branch_sql('WHERE', 'branch_id', 'e') : '';
$equipos_list = $conn->query("SELECT id, name, number_inventory FROM equipments e {$equip_where} ORDER BY name ASC");
$prefill_equipment_id = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : 0;

$inventario_data = [];
if ($conn) {
    $inv_where = function_exists('branch_sql') ? branch_sql('WHERE', 'branch_id', 'i') : '';
    $result = $conn->query("SELECT id, name, stock FROM inventory i {$inv_where} ORDER BY name ASC");
    if ($result && $result->num_rows > 0) {
        $inventario_data = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Reporte de Mantenimiento</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="./assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="assets/css/button-responsive.css">

    <style>
        body { background-color: #f4f7f6; }
        .report-form { max-width: 980px; margin: 30px auto; padding: 24px; background: #fff; border-radius: 12px; box-shadow: 0 10px 28px rgba(15, 23, 42, 0.10); }
        .section-header { background-color: #f0f8ff; border-left: 5px solid #007bff; padding: 5px 15px; margin-top: 20px; margin-bottom: 10px; font-weight: bold; }
        .contact-info div { line-height: 1.2; }
        .stock_indicator { font-size: 0.85rem; }
        .report-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 22px; padding: 18px 20px; border: 1px solid #dbe4f0; border-radius: 12px; background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%); }
        .report-brand { display: flex; align-items: flex-start; gap: 16px; min-width: 0; flex: 1 1 auto; }
        .report-brand-logo { width: 132px; min-width: 132px; height: 72px; border: 1px solid #d6e0ec; border-radius: 10px; background: #fff; display: flex; align-items: center; justify-content: center; padding: 8px; }
        .report-brand-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .report-brand-placeholder { font-size: 0.8rem; color: #7b8794; text-align: center; line-height: 1.3; }
        .report-brand-copy { min-width: 0; }
        .report-brand-copy .report-company { font-size: 1.2rem; font-weight: 700; color: #16283d; line-height: 1.2; }
        .report-brand-copy .report-meta { margin-top: 4px; color: #4b5b6b; }
        .report-brand-copy .report-description { margin-top: 8px; font-style: italic; color: #5c6f82; }
        .report-summary { min-width: 230px; max-width: 260px; padding-left: 18px; border-left: 1px solid #d6e0ec; text-align: right; }
        .report-summary .summary-title { margin: 0; font-size: 1.9rem; font-weight: 300; letter-spacing: 0.04em; color: #5c6f82; }
        .report-summary .summary-row { margin-top: 12px; color: #23384d; }
        .report-summary .summary-label { display: block; margin-bottom: 4px; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #6b7c8f; }
        .report-summary .summary-value { font-size: 1.05rem; font-weight: 700; }
        .report-summary .summary-badge { display: inline-block; padding: 7px 12px; border-radius: 10px; background: #fff1f0; border: 1px solid #f2c1bf; color: #8f2d2a; }

        /* === RESPONSIVE MOBILE === */
        @media (max-width: 767.98px) {
            .report-form {
                margin: 10px;
                padding: 15px;
            }

            .report-header {
                flex-direction: column;
                gap: 18px;
                padding: 16px;
            }

            .report-brand {
                flex-direction: column;
                gap: 12px;
            }

            .report-brand-logo {
                width: 100%;
                min-width: 0;
                height: 84px;
            }

            .report-summary {
                min-width: 0;
                max-width: none;
                width: 100%;
                padding-left: 0;
                padding-top: 14px;
                border-left: 0;
                border-top: 1px solid #d6e0ec;
                text-align: left;
            }

            /* Ocultar etiquetas col-form-label en mobile y mostrar arriba del input */
            .form-group.row {
                margin-bottom: 1rem;
            }

            .form-group.row .col-form-label {
                text-align: left !important;
                padding-bottom: 0.25rem;
                font-size: 0.875rem;
                font-weight: 600;
            }

            /* Ajustar sección de refacciones */
            .refaccion_item {
                margin-bottom: 1rem !important;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 6px;
            }

            .refaccion_item .col-6,
            .refaccion_item .col-3,
            .refaccion_item .col-2,
            .refaccion_item .col-1 {
                padding-left: 5px;
                padding-right: 5px;
            }

            /* Hacer que select de inventario ocupe más espacio */
            .refaccion_item > div:nth-child(1) {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 0.5rem;
            }

            /* Cantidad más pequeña */
            .refaccion_item > div:nth-child(2) {
                flex: 0 0 40%;
                max-width: 40%;
            }

            /* Stock indicator */
            .refaccion_item > div:nth-child(3) {
                flex: 0 0 50%;
                max-width: 50%;
                text-align: left !important;
            }

            /* Botón eliminar */
            .refaccion_item > div:nth-child(4) {
                flex: 0 0 10%;
                max-width: 10%;
                padding: 0;
            }

            .refaccion_item .btn-sm {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }

            /* Botón añadir item más pequeño */
            .section-header .btn-sm {
                padding: 0.35rem 0.7rem;
                font-size: 0.8rem;
            }

            /* Ajustar checkboxes inline para que no se salgan */
            .form-check-inline {
                margin-right: 0.5rem;
                font-size: 0.875rem;
            }

            /* Select2 en mobile */
            .select2-container {
                width: 100% !important;
            }

            /* Stock indicator badge */
            .stock_indicator {
                font-size: 0.7rem !important;
                padding: 0.25rem 0.4rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: inline-block;
                max-width: 100%;
            }

            /* Membrete responsive */
            .contact-info {
                font-size: 0.85rem;
            }
        }

        /* === TABLET === */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .refaccion_item > div:nth-child(1) {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .refaccion_item > div:nth-child(2) {
                flex: 0 0 25%;
                max-width: 25%;
            }

            .refaccion_item > div:nth-child(3) {
                flex: 0 0 20%;
                max-width: 20%;
            }

            .refaccion_item > div:nth-child(4) {
                flex: 0 0 5%;
                max-width: 5%;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">

<div class="wrapper">
    <!-- Content Wrapper -->
    <div class="content-wrapper" style="margin-left: 0 !important;">
        <section class="content">
            <div class="container-fluid">

                <div class="report-form">
                    <form action="<?= rtrim(BASE_URL, '/') ?>/legacy/generate_pdf.php" method="POST" id="reporteForm" target="_blank">
                        <input type="hidden" name="orden_mto" value="<?= $orden_mto ?>">
                        <input type="hidden" name="fecha_reporte" value="<?= $fecha_reporte ?>">
                        <input type="hidden" name="ingeniero_nombre" value="<?= $ingeniero_nombre ?>">
                        <input type="hidden" name="admin_name" value="<?= htmlspecialchars($current_user_name) ?>">

                        <!-- MEMBRETE -->
                        <div class="report-header">
                            <div class="report-brand">
                                <div class="report-brand-logo">
                                    <?php if (!empty($_company_logo_url)): ?>
                                        <img src="<?= htmlspecialchars($_company_logo_url) ?>" alt="Logo de la empresa">
                                    <?php else: ?>
                                        <div class="report-brand-placeholder">Espacio para logo<br>institucional</div>
                                    <?php endif; ?>
                                </div>
                                <div class="report-brand-copy contact-info">
                                    <div class="report-company"><?= htmlspecialchars($company_info['company_name']) ?></div>
                                    <div class="report-meta"><?= htmlspecialchars($company_info['address_line_1']) ?></div>
                                    <div class="report-meta"><?= htmlspecialchars($company_info['address_line_2']) ?></div>
                                    <div class="report-meta"><?= htmlspecialchars($company_info['city_state_zip']) ?></div>
                                    <div class="report-meta"><?= htmlspecialchars($company_info['phone_number']) ?></div>
                                    <?php if (!empty($_company_cfg['company_description'])): ?>
                                        <div class="report-description"><?= htmlspecialchars($_company_cfg['company_description']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="report-summary">
                                <h4 class="summary-title">Orden de Mantto</h4>
                                <div class="summary-row">
                                    <span class="summary-label">Orden</span>
                                    <span class="summary-value summary-badge"><?= $orden_mto ?></span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Fecha</span>
                                    <span class="summary-value"><?= $fecha_reporte ?></span>
                                </div>
                            </div>
                        </div>
                        <hr>

                        <!-- DATOS CLIENTE -->
                        <div class="section-header">DATOS DEL CLIENTE</div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group row">
                                    <label class="col-md-2 col-12 col-form-label">NOMBRE:</label>
                                    <div class="col-md-4 col-12 mb-2 mb-md-0"><input type="text" class="form-control form-control-sm" name="cliente_nombre" required></div>
                                    <label class="col-md-2 col-12 col-form-label">TEL:</label>
                                    <div class="col-md-4 col-12"><input type="text" class="form-control form-control-sm" name="cliente_tel"></div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-2 col-12 col-form-label">DOMICILIO:</label>
                                    <div class="col-md-6 col-12 mb-2 mb-md-0"><input type="text" class="form-control form-control-sm" name="cliente_domicilio"></div>
                                    <label class="col-md-1 col-12 col-form-label">E-MAIL:</label>
                                    <div class="col-md-3 col-12"><input type="email" class="form-control form-control-sm" name="cliente_email"></div>
                                </div>
                            </div>
                        </div>

                        <!-- DATOS EQUIPO -->
                        <div class="section-header">DATOS DEL EQUIPO</div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group row">
                                    <label class="col-md-2 col-12 col-form-label">SELECCIONE EQUIPO:</label>
                                    <div class="col-md-10 col-12">
                                        <select class="form-control form-control-sm select2" id="equipo_id_select" name="equipo_id_select" required>
                                            <option value="">Buscar y seleccionar equipo...</option>
                                            <?php while ($row = $equipos_list->fetch_assoc()): ?>
                                                <option value="<?= $row['id'] ?>" <?= $prefill_equipment_id === (int)$row['id'] ? 'selected' : '' ?>><?= htmlspecialchars($row['name']) . (!empty($row['number_inventory']) ? ' #' . htmlspecialchars($row['number_inventory']) : '') ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-2 col-12 col-form-label">NOMBRE:</label>
                                    <div class="col-md-4 col-12 mb-2 mb-md-0"><input type="text" class="form-control form-control-sm" name="equipo_nombre" id="equipo_nombre" readonly></div>
                                    <label class="col-md-2 col-12 col-form-label">MARCA:</label>
                                    <div class="col-md-4 col-12"><input type="text" class="form-control form-control-sm" name="equipo_marca" id="equipo_marca" readonly></div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-2 col-12 col-form-label">MODELO:</label>
                                    <div class="col-md-4 col-12 mb-2 mb-md-0"><input type="text" class="form-control form-control-sm" name="equipo_modelo" id="equipo_modelo" readonly></div>
                                    <label class="col-md-2 col-12 col-form-label">SERIE:</label>
                                    <div class="col-md-4 col-12"><input type="text" class="form-control form-control-sm" name="equipo_serie" id="equipo_serie" readonly></div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-2 col-12 col-form-label">INVENTARIO:</label>
                                    <div class="col-md-4 col-12 mb-2 mb-md-0"><input type="text" class="form-control form-control-sm" name="equipo_inventario" id="equipo_inventario" readonly></div>
                                    <label class="col-md-2 col-12 col-form-label">UBICACIÓN:</label>
                                    <div class="col-md-4 col-12"><input type="text" class="form-control form-control-sm" name="equipo_ubicacion" id="equipo_ubicacion" readonly></div>
                                    <input type="hidden" name="location_id" id="location_id_hidden">
                                </div>
                            </div>
                        </div>

                        <!-- TIPO DE SERVICIO -->
                        <div class="section-header">TIPO DE SERVICIO & EJECUCIÓN</div>
                        <div class="row mb-3">
                            <div class="col-12 mb-2">
                                <label class="d-block font-weight-bold">Tipo de Servicio:</label>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tipo_servicio" value="INSTALACION"><label class="form-check-label">INSTALACION</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tipo_servicio" value="MP" checked><label class="form-check-label">MP</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tipo_servicio" value="MC"><label class="form-check-label">MC</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tipo_servicio" value="SOPORTE TECNICO"><label class="form-check-label">SOPORTE TÉC.</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tipo_servicio" value="PREDICTIVO"><label class="form-check-label">PREDICTIVO</label></div>
                            </div>
                            <div class="col-12">
                                <label class="d-block font-weight-bold">Ejecución:</label>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="ejecucion" value="PLAZA" checked><label class="form-check-label">PLAZA</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="ejecucion" value="TALLER"><label class="form-check-label">TALLER</label></div>
                            </div>
                        </div>

                        <!-- HORARIO DE SERVICIO -->
                        <div class="section-header">HORARIO DE SERVICIO</div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha del Servicio:</label>
                                    <input type="date" class="form-control form-control-sm" name="service_date" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Hora Inicio:</label>
                                    <input type="time" class="form-control form-control-sm" name="service_start_time">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Hora Fin:</label>
                                    <input type="time" class="form-control form-control-sm" name="service_end_time">
                                </div>
                            </div>
                        </div>

                        <!-- DESCRIPCIÓN -->
                        <div class="section-header">DESCRIPCIÓN DEL TRABAJO</div>
                        <div class="form-group">
                            <textarea class="form-control" name="descripcion" rows="8"></textarea>
                        </div>

                        <!-- REFACCIONES -->
                        <div class="section-header d-flex justify-content-between align-items-center flex-wrap">
                            <span>REFACCIONES</span>
                            <button type="button" class="btn btn-success btn-sm mt-2 mt-md-0" id="add_refaccion_btn">
                                <i class="fas fa-plus"></i> Añadir Item
                            </button>
                        </div>

                        <div id="refacciones_container">
                            <!-- PRIMER ITEM -->
                            <div class="refaccion_item row mb-2" data-row-id="1">
                                <div class="col-md-6 col-12 mb-2 mb-md-0">
                                    <select class="form-control form-control-sm select2 inventory_select" name="refaccion_item_id[]" data-id="1" id="inventory_select_1">
                                        <option value="">Seleccionar Item</option>
                                        <?php foreach ($inventario_data as $row): ?>
                                            <option value="<?= $row['id'] ?>" data-stock="<?= $row['stock'] ?>">
                                                <?= htmlspecialchars($row['name']) ?> (Stock: <?= $row['stock'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-5 col-md-3 mb-2 mb-md-0">
                                    <input type="number" class="form-control form-control-sm refaccion_qty" name="refaccion_qty[]" value="1" min="1" data-id="1" placeholder="Cantidad">
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <span class="badge badge-secondary stock_indicator d-block" id="stock_indicator_1">Stock: N/A</span>
                                </div>
                                <div class="col-1 col-md-1"></div>
                            </div>
                        </div>

                        <!-- TEMPLATE -->
                        <template id="refaccion_template">
                            <div class="refaccion_item row mb-2">
                                <div class="col-md-6 col-12 mb-2 mb-md-0">
                                    <select class="form-control form-control-sm inventory_select" name="refaccion_item_id[]">
                                        <option value="">Seleccionar Item</option>
                                    </select>
                                </div>
                                <div class="col-5 col-md-3 mb-2 mb-md-0">
                                    <input type="number" class="form-control form-control-sm refaccion_qty" name="refaccion_qty[]" value="1" min="1" placeholder="Cantidad">
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <span class="badge badge-secondary stock_indicator d-block">Stock: N/A</span>
                                </div>
                                <div class="col-1 col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm remove_refaccion_btn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- OBSERVACIONES -->
                        <div class="section-header">OBSERVACIONES</div>
                        <div class="form-group">
                            <textarea class="form-control" name="observaciones" rows="3"></textarea>
                        </div>

                        <!-- STATUS Y FIRMAS -->
                        <div class="section-header">STATUS FINAL Y FIRMAS</div>
                        <div class="row">
                            <div class="col-12 mb-4">
                                <label>Status Final del Equipo:</label>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status_final" value="FUNCIONAL" checked><label class="form-check-label">FUNCIONAL</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status_final" value="STAND BY"><label class="form-check-label">STAND BY</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status_final" value="SIN REPARACION"><label class="form-check-label">SIN REPARACIÓN</label></div>
                            </div>
                        </div>

                        <div class="row text-center mt-4">
                            <div class="col-md-6 col-12 mb-4 mb-md-0">
                                <div class="mb-5">_________________________</div>
                                <div><strong>INGENIERO DE SERVICIO</strong></div>
                                <div><?= htmlspecialchars($ingeniero_nombre) ?></div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-5">_________________________</div>
                                <div><strong>RECIBE DE CONFORMIDAD</strong></div>
                                <input type="text" class="form-control form-control-sm text-center" name="recibe_nombre" placeholder="Nombre y Firma">
                            </div>
                        </div>

                        <!-- EVIDENCIA FOTOGRÁFICA -->
                        <div class="section-header">EVIDENCIA FOTOGRÁFICA</div>

                        <!-- Zona de subida -->
                        <div id="photo-drop-zone" style="border:2px dashed #6c757d;border-radius:6px;padding:18px;text-align:center;cursor:pointer;background:#fafafa;" class="mb-3">
                            <i class="fas fa-camera fa-2x text-secondary mb-2"></i>
                            <p class="mb-1 text-secondary">Arrastra imágenes aquí o haz clic para seleccionar</p>
                            <small class="text-muted">JPG / PNG — máx. 5 MB por foto — máx. 10 fotos</small>
                            <input type="file" id="photo-file-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple style="display:none;">
                        </div>

                        <!-- Miniaturas -->
                        <div id="photo-preview-area" class="row" style="gap:0;"></div>

                        <!-- Campo oculto con IDs de adjuntos -->
                        <input type="hidden" name="report_attachment_ids" id="report_attachment_ids" value="[]">

                        <hr class="my-4">
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-file-pdf mr-2"></i> Generar Reporte PDF
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </section>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/plugins/select2/js/select2.full.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="./assets/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    let refaccion_counter = 1;

    // === INICIALIZAR SOLO NUEVOS SELECTS ===
    function initNewSelect2() {
        $('.inventory_select:not(.select2-hidden-accessible)').select2({
            language: "es",
            width: '100%',
            placeholder: "Seleccionar Item"
        });
    }

    // === VALIDACIÓN DE STOCK ===
    function check_stock(e) {
        // DEFENSIVO: Validar que e.target existe
        if (!e || !e.target) return;
        
        const $target = $(e.target);
        if ($target.length === 0) return;
        
        // Evitar recursión infinita
        if ($target.data('checking') === true) return;
        $target.data('checking', true);
        
        try {
            const row = $target.closest('.refaccion_item');
            if (row.length === 0) return;
            
            const selected = row.find('.inventory_select option:selected');
            const qty = parseInt(row.find('.refaccion_qty').val()) || 0;
            const stock = parseInt(selected.data('stock')) || 0;
            const indicator = row.find('.stock_indicator');

            if (!selected.val()) {
                indicator.removeClass('badge-danger badge-success badge-warning').addClass('badge-secondary').text('Stock: N/A');
                return;
            }

            if (qty > stock) {
                indicator.removeClass('badge-success badge-warning').addClass('badge-danger').text(`¡Falta Stock! (${stock} disp.)`);
            } else if (qty <= 0) {
                indicator.removeClass('badge-danger badge-success').addClass('badge-warning').text(`Cantidad inválida`);
            } else {
                indicator.removeClass('badge-danger badge-warning').addClass('badge-success').text(`Stock OK (${stock} disp.)`);
            }
        } finally {
            $target.removeData('checking');
        }
    }

    // === AÑADIR FILA ===
    $('#add_refaccion_btn').on('click', function() {
        refaccion_counter++;
        const new_row = $('#refaccion_template').contents().clone();
        const select = new_row.find('.inventory_select');
        const qty = new_row.find('.refaccion_qty');
        const indicator = new_row.find('.stock_indicator');

        select.attr('id', `inventory_select_${refaccion_counter}`).attr('data-id', refaccion_counter);
        qty.attr('data-id', refaccion_counter);
        indicator.attr('id', `stock_indicator_${refaccion_counter}`);

        // LLENAR OPCIONES
        select.empty().append('<option value="">Seleccionar Item</option>');
        <?php foreach ($inventario_data as $row): ?>
            select.append('<option value="<?= $row['id'] ?>" data-stock="<?= $row['stock'] ?>"><?= htmlspecialchars($row['name']) ?> (Stock: <?= $row['stock'] ?>)</option>');
        <?php endforeach; ?>

        select.addClass('select2');

        $('#refacciones_container').append(new_row);
        initNewSelect2();
        // NO dispara trigger('change') para evitar recursión - el event delegation maneja el cambio
    });

    // === ELIMINAR FILA ===
    $(document).on('click', '.remove_refaccion_btn', function() {
        const row = $(this).closest('.refaccion_item');
        if ($('#refacciones_container .refaccion_item').length > 1) {
            row.find('.inventory_select').select2('destroy');
            row.remove();
        } else {
            alert("No puedes eliminar la última fila.");
        }
    });

    // === CARGAR EQUIPO ===
    function loadEquipmentDetails(id) {
        if (!id) {
            $('#equipo_nombre, #equipo_marca, #equipo_modelo, #equipo_serie, #equipo_inventario, #equipo_ubicacion, #location_id_hidden').val('');
            return;
        }
        $.post('public/ajax/action.php', { action: 'get_equipo_details', id: id }, function(resp) {
            if (resp.status === 1) {
                const d = resp.data;
                $('#equipo_nombre').val(d.name);
                $('#equipo_marca').val(d.brand);
                $('#equipo_modelo').val(d.model);
                $('#equipo_serie').val(d.serie);
                $('#equipo_inventario').val(d.number_inventory);
                $('#equipo_ubicacion').val(d.location_name);
                $('#location_id_hidden').val(d.location_id);
            }
        }, 'json');
    }

    $('#equipo_id_select').on('change', function() {
        const id = $(this).val();
        loadEquipmentDetails(id);
    });

    // === ENVÍO ===
   $('#reporteForm').on('submit', function(e) {
    // Validar stock ANTES de enviar
    let valid = true;
    $('.refaccion_item').each(function() {
        const sel = $(this).find('.inventory_select option:selected');
        const qty = parseInt($(this).find('.refaccion_qty').val()) || 0;
        const stock = parseInt(sel.data('stock')) || 0;
        if (sel.val() && qty > stock) valid = false;
    });

    if (!valid) {
        e.preventDefault();
        alert("Corrige el stock antes de continuar.");
    }
});

    // === INICIALIZAR AL CARGAR ===
    initNewSelect2();
    $(document).on('change input', '.refaccion_qty, .inventory_select', check_stock);
    
    // Validar estado inicial sin trigger (SEGURO: verificar que elemento existe)
    const initial_select = $('#inventory_select_1');
    if (initial_select.length && initial_select.find('option:selected').val()) {
        // Crear evento seguro
        const evt = { target: initial_select[0] };
        check_stock.call(initial_select[0], evt);
    }

    if ($('#equipo_id_select').val()) {
        loadEquipmentDetails($('#equipo_id_select').val());
    }

    // ===================================================
    // EVIDENCIA FOTOGRÁFICA – subida AJAX temporal
    // ===================================================
    const ATTACH_ENDPOINT = 'public/ajax/report_attachment.php';
    const MAX_PHOTOS      = 10;
    let   attachmentIds   = [];

    function syncHiddenField() {
        $('#report_attachment_ids').val(JSON.stringify(attachmentIds));
    }

    function addThumbnail(id, filePath) {
        const baseUrl = '<?= rtrim(BASE_URL, '/') ?>/';
        const src     = baseUrl + filePath;
        const card    = $(`
            <div class="col-6 col-md-3 mb-3 photo-thumb" data-id="${id}">
                <div style="position:relative;border:1px solid #dee2e6;border-radius:4px;overflow:hidden;height:110px;background:#000;">
                    <img src="${src}" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                    <button type="button" class="btn-remove-photo" title="Eliminar"
                        style="position:absolute;top:4px;right:4px;background:rgba(220,53,69,.85);color:#fff;border:none;border-radius:50%;width:24px;height:24px;line-height:24px;font-size:14px;cursor:pointer;padding:0;">
                        &times;
                    </button>
                </div>
            </div>
        `);
        $('#photo-preview-area').append(card);
    }

    function uploadFile(file) {
        if (attachmentIds.length >= MAX_PHOTOS) {
            alert('Límite de ' + MAX_PHOTOS + ' fotos alcanzado.');
            return;
        }

        const formData = new FormData();
        formData.append('photo', file);

        $.ajax({
            url: ATTACH_ENDPOINT + '?action=upload',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    attachmentIds.push(res.id);
                    syncHiddenField();
                    addThumbnail(res.id, res.file_path);
                } else {
                    alert('Error al subir foto: ' + res.message);
                }
            },
            error: function () {
                alert('Error de comunicación al subir la foto.');
            }
        });
    }

    // Click en zona → abrir selector
    $('#photo-drop-zone').on('click', function () {
        $('#photo-file-input').trigger('click');
    });

    // Selección via input file
    $('#photo-file-input').on('change', function () {
        $.each(this.files, function (i, f) { uploadFile(f); });
        $(this).val('');   // resetear para permitir re-selección
    });

    // Drag & Drop
    $('#photo-drop-zone').on('dragover dragenter', function (e) {
        e.preventDefault();
        $(this).css('background', '#e8f4fe');
    }).on('dragleave dragexit', function () {
        $(this).css('background', '#fafafa');
    }).on('drop', function (e) {
        e.preventDefault();
        $(this).css('background', '#fafafa');
        const files = e.originalEvent.dataTransfer.files;
        $.each(files, function (i, f) { uploadFile(f); });
    });

    // Eliminar miniatura
    $(document).on('click', '.btn-remove-photo', function () {
        const card = $(this).closest('.photo-thumb');
        const id   = parseInt(card.data('id'));

        $.post(ATTACH_ENDPOINT + '?action=delete', { id: id }, function (res) {
            if (res.success) {
                attachmentIds = attachmentIds.filter(function (v) { return v !== id; });
                syncHiddenField();
                card.remove();
            } else {
                alert('No se pudo eliminar la foto.');
            }
        }, 'json').fail(function () {
            alert('Error de comunicación al eliminar la foto.');
        });
    });
});
</script>

</body>
</html>

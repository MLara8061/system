<?php
/**
 * CustomFieldRenderer - Helper para renderizar campos personalizados en formularios
 *
 * Uso en cada formulario:
 *   <?php require_once ROOT . '/app/helpers/CustomFieldRenderer.php'; ?>
 *   ...
 *   <?= CustomFieldRenderer::render('tool', $entityId) ?>
 */

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

class CustomFieldRenderer {

    /**
     * Genera el HTML del bloque de campos personalizados.
     *
     * @param  string   $entityType  'equipment'|'tool'|'accessory'|'inventory'
     * @param  int      $entityId    ID de la entidad (0 para crear)
     * @return string
     */
    public static function render(string $entityType, int $entityId = 0): string {
        try {
            require_once ROOT . '/config/db.php';
            $pdo = get_pdo();

            // Verificar que la tabla existe antes de consultar
            $chk = $pdo->query("SHOW TABLES LIKE 'custom_field_definitions'");
            if (!$chk || $chk->rowCount() === 0) {
                return '';  // Migración aún no ejecutada
            }

            $branchId = function_exists('active_branch_id')
                ? (int)active_branch_id()
                : (int)($_SESSION['login_active_branch_id'] ?? 0);

            $stmt = $pdo->prepare(
                "SELECT * FROM custom_field_definitions
                  WHERE entity_type = :type
                    AND active = 1
                    AND (:bid = 0 OR branch_id IS NULL OR branch_id = :bid2)
                  ORDER BY sort_order ASC, id ASC"
            );
            $stmt->execute([':type' => $entityType, ':bid' => $branchId, ':bid2' => $branchId]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($fields)) {
                return '';   // Nada que mostrar
            }

            // Obtener valores guardados si es edición
            $savedValues = [];
            if ($entityId > 0) {
                $vs = $pdo->prepare(
                    "SELECT definition_id, field_value FROM custom_field_values
                      WHERE entity_type = :type AND entity_id = :eid"
                );
                $vs->execute([':type' => $entityType, ':eid' => $entityId]);
                foreach ($vs->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $savedValues[(int)$row['definition_id']] = $row['field_value'];
                }
            }

            return self::buildHtml($fields, $savedValues);

        } catch (Throwable $e) {
            error_log('CustomFieldRenderer::render error: ' . $e->getMessage());
            return '';
        }
    }

    // -----------------------------------------------------------------------
    private static function buildHtml(array $fields, array $savedValues): string {
        $html  = '<div class="card mb-4 border-primary" id="custom-fields-block">';
        $html .= '<div class="card-header bg-white border-0 py-2">';
        $html .= '<h6 class="mb-0 text-primary"><i class="fas fa-sliders-h mr-2"></i>Campos adicionales</h6>';
        $html .= '</div>';
        $html .= '<div class="card-body pt-0">';
        $html .= '<div class="row">';

        foreach ($fields as $f) {
            $defId   = (int)$f['id'];
            $label   = htmlspecialchars($f['field_label']);
            $type    = $f['field_type'];
            $name    = 'cf[' . $defId . ']';
            $req     = $f['is_required'] ? 'required' : '';
            $current = htmlspecialchars($savedValues[$defId] ?? '');

            $input = self::renderInput($type, $name, $current, $req, $f['options'] ?? null);

            $html .= '<div class="col-md-6 mb-3">';
            $html .= '<label class="font-weight-bold text-dark">' . $label;
            if ($f['is_required']) $html .= ' <span class="text-danger">*</span>';
            $html .= '</label>';
            $html .= $input;
            $html .= '</div>';
        }

        $html .= '</div></div></div>';
        return $html;
    }

    // -----------------------------------------------------------------------
    private static function renderInput(
        string  $type,
        string  $name,
        string  $current,
        string  $req,
        ?string $optionsJson
    ): string {
        switch ($type) {
            case 'textarea':
                return '<textarea name="' . $name . '" class="form-control" rows="2" ' . $req . '>'
                    . $current . '</textarea>';

            case 'number':
                return '<input type="number" name="' . $name . '" class="form-control"'
                    . ' value="' . $current . '" ' . $req . '>';

            case 'date':
                return '<input type="date" name="' . $name . '" class="form-control"'
                    . ' value="' . $current . '" ' . $req . '>';

            case 'checkbox':
                $checked = $current === '1' ? 'checked' : '';
                return '<div class="form-check mt-2">'
                    . '<input type="hidden" name="' . $name . '" value="0">'
                    . '<input type="checkbox" name="' . $name . '" class="form-check-input" value="1" ' . $checked . ' ' . $req . ' id="' . $name . '">'
                    . '</div>';

            case 'select':
                $options   = $optionsJson ? (json_decode($optionsJson, true) ?: []) : [];
                $selectHtml = '<select name="' . $name . '" class="custom-select" ' . $req . '>';
                $selectHtml .= '<option value="">Seleccionar...</option>';
                foreach ($options as $opt) {
                    $opt = htmlspecialchars((string)$opt);
                    $sel = $current === $opt ? 'selected' : '';
                    $selectHtml .= '<option value="' . $opt . '" ' . $sel . '>' . $opt . '</option>';
                }
                $selectHtml .= '</select>';
                return $selectHtml;

            default: // text
                return '<input type="text" name="' . $name . '" class="form-control"'
                    . ' value="' . $current . '" ' . $req . '>';
        }
    }
}

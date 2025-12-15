<?php
/**
 * Descarga directa del Manual de Usuario en formato PDF
 * Usa el navegador para convertir HTML a PDF
 */

// Redirigir al manual con parámetro para forzar descarga
header('Location: app/helpers/manual_usuario_pdf.php?download=1');
exit();

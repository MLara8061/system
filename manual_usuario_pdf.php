<?php
/**
 * Manual de Usuario del Sistema de Gestión de Activos
 * Genera un PDF profesional con instrucciones detalladas paso a paso
 */

require_once 'config/config.php';

// Configuración de encoding
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$title = "Manual de Usuario - Sistema de Gestión de Activos";
$version = "1.0";
$date = date('d/m/Y');

// Estructura del manual
$manual = [
    'intro' => [
        'title' => '1. Introducción al Sistema',
        'content' => 'El Sistema de Gestión de Activos es una plataforma integral diseñada para administrar equipos, herramientas, accesorios, proveedores, mantenimientos y tickets de soporte técnico. Permite registrar, dar seguimiento y generar reportes de todos los activos de la organización.',
        'features' => [
            'Gestión completa de equipos médicos y tecnológicos',
            'Control de inventario de herramientas y accesorios',
            'Administración de proveedores y servicios',
            'Calendario de mantenimientos preventivos y correctivos',
            'Sistema de tickets de soporte técnico',
            'Generación de reportes y estadísticas',
            'Carga masiva de equipos mediante Excel',
            'Códigos QR para identificación rápida',
            'Control de bajas de equipos con registro de folios'
        ]
    ],
    
    'access' => [
        'title' => '2. Acceso al Sistema',
        'sections' => [
            [
                'subtitle' => '2.1 Inicio de Sesión',
                'steps' => [
                    'Abrir el navegador web (Chrome, Firefox, Edge)',
                    'Ingresar la URL del sistema proporcionada por su administrador',
                    'En la pantalla de login, introducir su nombre de usuario',
                    'Introducir su contraseña (puede mostrarla haciendo clic en el ícono del ojo)',
                    'Hacer clic en el botón "Acceder"',
                    'El sistema validará sus credenciales y lo redirigirá al Dashboard principal'
                ],
                'notes' => 'Si olvida su contraseña, contacte al administrador del sistema para restablecerla.'
            ],
            [
                'subtitle' => '2.2 Tipos de Usuario',
                'content' => 'El sistema cuenta con dos niveles de acceso:',
                'items' => [
                    '<strong>Administrador:</strong> Acceso completo a todos los módulos, puede crear usuarios, configurar catálogos, visualizar registros de actividad y realizar todas las operaciones del sistema.',
                    '<strong>Usuario Regular:</strong> Acceso a módulos de consulta y registro de equipos, herramientas, tickets y reportes. No tiene acceso a configuración ni administración de usuarios.'
                ]
            ]
        ]
    ],
    
    'dashboard' => [
        'title' => '3. Dashboard Principal',
        'content' => 'Al iniciar sesión, verá el Dashboard con estadísticas generales del sistema:',
        'cards' => [
            '<strong>Total de Equipos:</strong> Cantidad total de equipos registrados',
            '<strong>Total de Accesorios:</strong> Inventario de accesorios disponibles',
            '<strong>Total de Herramientas:</strong> Cantidad de herramientas registradas',
            '<strong>Valor Total:</strong> Suma del valor monetario de todos los activos'
        ],
        'sections' => [
            'Gráficos de equipos por ubicación',
            'Distribución de equipos por departamento',
            'Mantenimientos programados próximos',
            'Tickets pendientes de atención'
        ]
    ],
    
    'equipos' => [
        'title' => '4. Gestión de Equipos',
        'modules' => [
            [
                'subtitle' => '4.1 Ingresar Nuevo Equipo',
                'steps' => [
                    'En el menú lateral, hacer clic en "Equipos" > "Ingresar Equipo"',
                    'Completar el formulario con los siguientes campos obligatorios:',
                    '  • <strong>Nombre del equipo:</strong> Tipo de equipo (ej. Monitor de signos vitales)',
                    '  • <strong>Número de inventario:</strong> Código único de identificación',
                    '  • <strong>Marca:</strong> Fabricante del equipo',
                    '  • <strong>Modelo:</strong> Modelo específico',
                    '  • <strong>Serie:</strong> Número de serie del fabricante',
                    '  • <strong>Departamento:</strong> Área donde se ubicará',
                    '  • <strong>Ubicación:</strong> Sala o espacio específico',
                    '  • <strong>Responsable:</strong> Persona a cargo del equipo',
                    '  • <strong>Puesto:</strong> Cargo del responsable',
                    '  • <strong>Monto:</strong> Valor en moneda local',
                    'Opcionalmente, completar campos adicionales como proveedor, fecha de adquisición, garantía',
                    'Hacer clic en el botón "Guardar" para registrar el equipo',
                    'El sistema confirmará el registro con un mensaje de éxito',
                    'Puede imprimir la etiqueta QR del equipo desde el listado'
                ],
                'tips' => 'Asegúrese de que el número de inventario sea único para evitar duplicados.'
            ],
            [
                'subtitle' => '4.2 Consultar Todos los Equipos',
                'steps' => [
                    'Ir a "Equipos" > "Todos Los Equipos"',
                    'Verá una tabla con todos los equipos registrados',
                    'Use la barra de búsqueda superior para filtrar por cualquier campo',
                    'Puede exportar la tabla a Excel o PDF usando los botones superiores',
                    'Haga clic en el botón "Ver" para consultar los detalles completos',
                    'Use "Editar" para modificar información del equipo',
                    'El botón "QR" genera e imprime la etiqueta de código QR'
                ],
                'features' => [
                    'Búsqueda en tiempo real por cualquier campo',
                    'Paginación automática de resultados',
                    'Ordenamiento por columnas',
                    'Exportación a Excel, PDF o impresión directa'
                ]
            ],
            [
                'subtitle' => '4.3 Reporte de Sistemas',
                'steps' => [
                    'Acceder a "Equipos" > "Reporte de Sistemas"',
                    'Seleccionar el equipo del listado',
                    'Completar el formulario de reporte con:',
                    '  • Orden de mantenimiento',
                    '  • Fecha y hora del reporte',
                    '  • Tipo de servicio (preventivo, correctivo, instalación)',
                    '  • Tipo de ejecución (interno o externo)',
                    '  • Nombre del ingeniero a cargo',
                    '  • Observaciones y trabajos realizados',
                    '  • Estado final del equipo',
                    'Guardar el reporte',
                    'El sistema generará un PDF automáticamente que puede descargar'
                ],
                'notes' => 'Los reportes quedan vinculados al equipo y se pueden consultar en su historial.'
            ],
            [
                'subtitle' => '4.4 Revisiones Mensual',
                'content' => 'Este módulo permite visualizar y programar las revisiones periódicas de equipos:',
                'steps' => [
                    'Ir a "Equipos" > "Revisiones Mensual"',
                    'Seleccionar el mes y año a consultar',
                    'Ver la lista de equipos que requieren revisión',
                    'Hacer clic en "Revisar" para registrar la inspección',
                    'Completar los campos de estado, observaciones y responsable',
                    'Guardar la revisión',
                    'El sistema actualizará la fecha de última revisión'
                ]
            ],
            [
                'subtitle' => '4.5 Dar de Baja un Equipo',
                'steps' => [
                    'Desde el listado de equipos, hacer clic en el botón "Baja" del equipo deseado',
                    'El sistema mostrará el formulario de baja con los datos del equipo',
                    'Completar la información requerida:',
                    '  • <strong>Folio:</strong> Se genera automáticamente con formato BAJ-AAAA-NNNN',
                    '  • <strong>Responsable:</strong> Jefe de servicio o Proveedor externo',
                    '  • <strong>Destino:</strong> Bodega, devolución, donación, venta o basura',
                    '  • <strong>Dictamen:</strong> Funcional o Disfuncional',
                    '  • <strong>Causas de baja:</strong> Seleccionar una o múltiples razones',
                    '  • <strong>Observaciones:</strong> Detalles adicionales',
                    'Hacer clic en "Registrar Baja"',
                    'El sistema mostrará una alerta de confirmación moderna',
                    'Confirmar la acción',
                    'Automáticamente se abrirá el PDF del formato de baja',
                    'Imprimir o descargar el documento para archivo físico'
                ],
                'notes' => 'Una vez dado de baja, el equipo queda fuera del inventario activo pero se conserva su historial completo.'
            ],
            [
                'subtitle' => '4.6 Consultar Bajas Registradas',
                'steps' => [
                    'Ir a "Equipos" > "Bajas registradas"',
                    'Verá una tabla con todas las bajas realizadas',
                    'La tabla muestra: folio, equipo, fecha, usuario que registró, responsable, destino, dictamen y causas',
                    'Use el buscador para filtrar por folio, equipo o fecha',
                    'Haga clic en "PDF" para reimprimir el formato de baja'
                ],
                'features' => [
                    'Historial completo de bajas',
                    'Información de usuario que procesó cada baja',
                    'Generación de PDF en cualquier momento',
                    'Exportación del reporte global a Excel'
                ]
            ]
        ]
    ],
    
    'proveedores' => [
        'title' => '5. Gestión de Proveedores',
        'modules' => [
            [
                'subtitle' => '5.1 Agregar Proveedor',
                'steps' => [
                    'Ir a "Proveedores" > "Agregar"',
                    'Llenar el formulario con:',
                    '  • Nombre completo del proveedor',
                    '  • Razón social',
                    '  • RFC o identificación fiscal',
                    '  • Dirección',
                    '  • Teléfono',
                    '  • Correo electrónico',
                    '  • Persona de contacto',
                    '  • Servicios que ofrece',
                    'Hacer clic en "Guardar"',
                    'El proveedor quedará disponible para asignar a equipos y servicios'
                ]
            ],
            [
                'subtitle' => '5.2 Consultar Todos los Proveedores',
                'steps' => [
                    'Acceder a "Proveedores" > "Todos los proveedores"',
                    'Visualizar la tabla con todos los registros',
                    'Usar la búsqueda para localizar proveedores específicos',
                    'Hacer clic en "Editar" para modificar datos',
                    'Usar "Eliminar" para dar de baja un proveedor (requiere confirmación)',
                    'Exportar el listado a Excel usando el botón correspondiente'
                ]
            ]
        ]
    ],
    
    'herramientas' => [
        'title' => '6. Gestión de Herramientas',
        'modules' => [
            [
                'subtitle' => '6.1 Ingresar Herramienta',
                'steps' => [
                    'Ir a "Herramientas" > "Ingresar Herramienta"',
                    'Completar el formulario:',
                    '  • Nombre de la herramienta',
                    '  • Código o número de identificación',
                    '  • Marca',
                    '  • Modelo',
                    '  • Ubicación actual',
                    '  • Costo',
                    '  • Estado (bueno, regular, malo)',
                    '  • Observaciones',
                    'Guardar el registro',
                    'La herramienta aparecerá en el inventario'
                ]
            ],
            [
                'subtitle' => '6.2 Consultar Todas las Herramientas',
                'steps' => [
                    'Acceder a "Herramientas" > "Todas las Herramientas"',
                    'Ver la tabla con todas las herramientas',
                    'Buscar por nombre, código o ubicación',
                    'Editar o eliminar registros según sea necesario',
                    'Exportar el inventario a Excel'
                ]
            ]
        ]
    ],
    
    'accesorios' => [
        'title' => '7. Gestión de Accesorios',
        'modules' => [
            [
                'subtitle' => '7.1 Ingresar Accesorios',
                'steps' => [
                    'Ir a "Accesorios" > "Ingresar"',
                    'Llenar el formulario:',
                    '  • Nombre del accesorio',
                    '  • Código',
                    '  • Cantidad disponible',
                    '  • Costo unitario',
                    '  • Proveedor',
                    '  • Ubicación de almacenamiento',
                    'Guardar el accesorio'
                ]
            ],
            [
                'subtitle' => '7.2 Todos los Accesorios',
                'steps' => [
                    'Acceder a "Accesorios" > "Todos los Accesorios"',
                    'Consultar el inventario completo',
                    'Filtrar por nombre o código',
                    'Editar cantidades y precios',
                    'Exportar inventario'
                ]
            ]
        ]
    ],
    
    'inventario' => [
        'title' => '8. Gestión de Inventario',
        'content' => 'El módulo de inventario permite controlar artículos adicionales no clasificados como equipos, herramientas o accesorios.',
        'modules' => [
            [
                'subtitle' => '8.1 Ingresar Artículo',
                'steps' => [
                    'Ir a "Inventario" > "Ingresar"',
                    'Completar datos del artículo',
                    'Guardar registro'
                ]
            ],
            [
                'subtitle' => '8.2 Consultar Inventario',
                'steps' => [
                    'Acceder a "Inventario" > "Todos"',
                    'Ver listado completo',
                    'Realizar búsquedas y exportaciones'
                ]
            ]
        ]
    ],
    
    'mantenimientos' => [
        'title' => '9. Calendario de Mantenimientos',
        'content' => 'El módulo de Mantenimientos permite programar y dar seguimiento a servicios preventivos y correctivos.',
        'steps' => [
            'Acceder a "Mantenimientos" en el menú principal',
            'Ver el calendario mensual con todos los mantenimientos programados',
            'Los eventos se colorean según su tipo:',
            '  • <strong style="color:#28a745;">Verde:</strong> Mantenimiento preventivo',
            '  • <strong style="color:#dc3545;">Rojo:</strong> Mantenimiento correctivo',
            '  • <strong style="color:#007bff;">Azul:</strong> Instalación o calibración',
            'Hacer clic en un evento para ver los detalles',
            'Desde el evento puede:',
            '  • Ver información del equipo',
            '  • Editar la fecha/hora del mantenimiento',
            '  • Marcar como completado',
            '  • Generar reporte del servicio realizado',
            'Use los botones "Mes", "Semana", "Día" para cambiar la vista',
            'El botón "Hoy" regresa a la fecha actual'
        ],
        'notes' => 'Los mantenimientos se crean automáticamente al registrar un reporte de sistema con fecha futura.'
    ],
    
    'tickets' => [
        'title' => '10. Sistema de Tickets de Soporte',
        'modules' => [
            [
                'subtitle' => '10.1 Crear Nuevo Ticket',
                'steps' => [
                    'Ir a "Tickets de Soporte" > "Nuevo Ticket"',
                    'Completar el formulario:',
                    '  • <strong>Asunto:</strong> Título breve del problema',
                    '  • <strong>Departamento:</strong> Área que reporta',
                    '  • <strong>Prioridad:</strong> Baja, Media, Alta, Urgente',
                    '  • <strong>Descripción:</strong> Detalle completo del problema',
                    '  • <strong>Equipo relacionado:</strong> (opcional) Si aplica a un equipo específico',
                    'Hacer clic en "Crear Ticket"',
                    'El sistema asignará un número de ticket automáticamente',
                    'Recibirá confirmación del registro'
                ]
            ],
            [
                'subtitle' => '10.2 Consultar Tickets',
                'steps' => [
                    'Acceder a "Tickets de Soporte" > "Todos los Tickets"',
                    'Ver la tabla con todos los tickets',
                    'Los tickets se muestran con colores según su estado:',
                    '  • <strong style="color:#ffc107;">Amarillo:</strong> Pendiente',
                    '  • <strong style="color:#007bff;">Azul:</strong> En proceso',
                    '  • <strong style="color:#28a745;">Verde:</strong> Resuelto',
                    '  • <strong style="color:#dc3545;">Rojo:</strong> Cerrado',
                    'Filtrar por estado, prioridad o departamento',
                    'Hacer clic en "Ver" para abrir el ticket completo'
                ]
            ],
            [
                'subtitle' => '10.3 Dar Seguimiento a un Ticket',
                'steps' => [
                    'Abrir el ticket desde el listado',
                    'En la vista de detalle puede:',
                    '  • Ver toda la información del ticket',
                    '  • Agregar comentarios y actualizaciones',
                    '  • Cambiar el estado (pendiente, en proceso, resuelto)',
                    '  • Reasignar a otro técnico',
                    '  • Adjuntar archivos o imágenes',
                    '  • Cerrar el ticket cuando esté resuelto',
                    'Cada acción quedará registrada en el historial del ticket'
                ]
            ]
        ]
    ],
    
    'reportes' => [
        'title' => '11. Generación de Reportes',
        'content' => 'El sistema permite generar reportes personalizados en PDF con filtros específicos.',
        'steps' => [
            'Ir a "Generar Reportes" en el menú principal',
            'Seleccionar el tipo de reporte deseado:',
            '  • Reporte de equipos por departamento',
            '  • Reporte de equipos por ubicación',
            '  • Reporte de mantenimientos realizados',
            '  • Reporte de tickets por estado',
            '  • Reporte de inventario valorizado',
            'Aplicar filtros según el tipo de reporte:',
            '  • Rango de fechas',
            '  • Departamento específico',
            '  • Estado de equipos',
            '  • Tipo de servicio',
            'Hacer clic en "Generar Reporte"',
            'El sistema procesará la información y generará un PDF',
            'Descargar o imprimir el reporte generado'
        ],
        'notes' => 'Los reportes se generan en tiempo real con la información actualizada de la base de datos.'
    ],
    
    'admin' => [
        'title' => '12. Configuración del Sistema (Solo Administradores)',
        'content' => 'Los usuarios con rol de Administrador tienen acceso a módulos adicionales de configuración.',
        'modules' => [
            [
                'subtitle' => '12.1 Gestión de Departamentos',
                'steps' => [
                    'Ir a "Configuración" > "Departamentos"',
                    'Ver listado de departamentos existentes',
                    'Hacer clic en "Agregar Departamento"',
                    'Ingresar el nombre del nuevo departamento',
                    'Guardar',
                    'Editar o eliminar departamentos según sea necesario'
                ]
            ],
            [
                'subtitle' => '12.2 Gestión de Servicios',
                'content' => 'Administrar las categorías y tipos de servicios de mantenimiento.',
                'steps' => [
                    'Acceder a "Configuración" > "Servicios"',
                    'Crear nuevas categorías desde "Nueva Categoría"',
                    'Crear servicios específicos desde "Crear Servicios"',
                    'Asignar categorías a los servicios',
                    'Consultar listas de categorías y servicios'
                ]
            ],
            [
                'subtitle' => '12.3 Gestión de Ubicaciones',
                'steps' => [
                    'Ir a "Configuración" > "Ubicaciones" > "Lista de Ubicaciones"',
                    'Hacer clic en "Agregar Ubicación"',
                    'Ingresar el nombre de la sala, área o espacio',
                    'Asignar al departamento correspondiente',
                    'Guardar la ubicación'
                ]
            ],
            [
                'subtitle' => '12.4 Gestión de Puestos',
                'steps' => [
                    'Acceder a "Configuración" > "Puestos" > "Lista de Puestos"',
                    'Crear nuevos puestos o cargos',
                    'Editar o eliminar puestos existentes'
                ]
            ],
            [
                'subtitle' => '12.5 Gestión de Usuarios',
                'steps' => [
                    'Ir a "Configuración" > "Usuarios" > "Todos los Usuarios"',
                    'Ver listado de usuarios del sistema',
                    'Hacer clic en "Crear Usuario"',
                    'Completar el formulario:',
                    '  • Nombre completo',
                    '  • Nombre de usuario (login)',
                    '  • Correo electrónico',
                    '  • Contraseña',
                    '  • Rol: Administrador o Usuario Regular',
                    '  • Avatar (opcional)',
                    'Guardar el usuario',
                    'Desde el listado puede editar usuarios existentes',
                    'Puede desactivar usuarios sin eliminarlos'
                ],
                'notes' => 'Solo los administradores pueden crear, editar o eliminar usuarios. Cada usuario debe tener un nombre de usuario único.'
            ],
            [
                'subtitle' => '12.6 Carga Masiva de Equipos',
                'steps' => [
                    'Ir a "Configuración" > "Carga Masiva" > "Equipos desde Excel"',
                    'Descargar la plantilla de Excel proporcionada',
                    'Abrir la plantilla en Excel o LibreOffice',
                    'Llenar las filas con los datos de cada equipo:',
                    '  • Respetar el orden de las columnas',
                    '  • No modificar los encabezados',
                    '  • Usar el formato de fecha indicado',
                    '  • Verificar que no haya números de inventario duplicados',
                    'Guardar el archivo Excel',
                    'Regresar al sistema y hacer clic en "Seleccionar archivo"',
                    'Seleccionar el archivo Excel preparado',
                    'Hacer clic en "Cargar Equipos"',
                    'El sistema procesará el archivo y mostrará un resumen:',
                    '  • Equipos cargados correctamente',
                    '  • Errores detectados (si los hay)',
                    'Revisar el resultado y corregir errores si es necesario',
                    'Los equipos cargados aparecerán en el listado general'
                ],
                'notes' => 'Antes de cargar, verifique que todos los datos sean correctos. La carga masiva no se puede deshacer fácilmente.'
            ],
            [
                'subtitle' => '12.7 Registro de Actividad',
                'content' => 'Este módulo muestra un log completo de todas las acciones realizadas en el sistema.',
                'steps' => [
                    'Acceder a "Registro de Actividad"',
                    'Ver la tabla cronológica con:',
                    '  • Fecha y hora de cada acción',
                    '  • Usuario que realizó la acción',
                    '  • Tipo de acción (crear, editar, eliminar)',
                    '  • Módulo afectado',
                    '  • Detalle de la operación',
                    'Usar filtros para buscar actividades específicas',
                    'Exportar el log a Excel para auditorías'
                ],
                'notes' => 'El registro de actividad solo es visible para administradores y es útil para auditorías y trazabilidad.'
            ]
        ]
    ],
    
    'tips' => [
        'title' => '13. Consejos y Buenas Prácticas',
        'items' => [
            '<strong>Respalde regularmente:</strong> El administrador debe realizar respaldos periódicos de la base de datos.',
            '<strong>Números de inventario únicos:</strong> Asegúrese de que cada equipo tenga un número de inventario único.',
            '<strong>Mantenga actualizada la información:</strong> Actualice regularmente datos de contacto, ubicaciones y responsables.',
            '<strong>Use el calendario de mantenimientos:</strong> Programe mantenimientos preventivos para evitar fallas.',
            '<strong>Etiquete con códigos QR:</strong> Imprima y pegue las etiquetas QR en los equipos para identificación rápida.',
            '<strong>Documente observaciones:</strong> Use los campos de observaciones para registrar detalles importantes.',
            '<strong>Cierre los tickets resueltos:</strong> Mantenga actualizado el estado de los tickets de soporte.',
            '<strong>Revise el registro de actividad:</strong> Consulte periódicamente el log para detectar anomalías.',
            '<strong>Capacite a los usuarios:</strong> Asegúrese de que todos los usuarios conozcan sus funciones y permisos.',
            '<strong>Proteja sus credenciales:</strong> No comparta su contraseña y cierre sesión al terminar.'
        ]
    ],
    
    'troubleshooting' => [
        'title' => '14. Solución de Problemas Comunes',
        'issues' => [
            [
                'problem' => 'No puedo iniciar sesión',
                'solutions' => [
                    'Verifique que está usando el nombre de usuario correcto (no el nombre completo)',
                    'Asegúrese de que la contraseña es correcta (distingue mayúsculas y minúsculas)',
                    'Contacte al administrador para restablecer su contraseña'
                ]
            ],
            [
                'problem' => 'No veo el menú de Configuración',
                'solutions' => [
                    'Este menú solo es visible para usuarios con rol de Administrador',
                    'Contacte a su administrador si necesita acceso'
                ]
            ],
            [
                'problem' => 'El número de inventario ya existe',
                'solutions' => [
                    'Cada equipo debe tener un número de inventario único',
                    'Verifique en el listado de equipos si ya existe',
                    'Use un número diferente o agregue un sufijo'
                ]
            ],
            [
                'problem' => 'No se genera el PDF',
                'solutions' => [
                    'Verifique que su navegador permite descargas',
                    'Desactive el bloqueador de ventanas emergentes',
                    'Intente con otro navegador (Chrome recomendado)',
                    'Contacte al administrador si el problema persiste'
                ]
            ],
            [
                'problem' => 'No puedo cargar el archivo Excel',
                'solutions' => [
                    'Verifique que está usando la plantilla oficial descargada del sistema',
                    'Asegúrese de que no modificó los encabezados de las columnas',
                    'Revise que el formato de las fechas sea correcto',
                    'Verifique que no hay números de inventario duplicados'
                ]
            ],
            [
                'problem' => 'El código QR no se imprime correctamente',
                'solutions' => [
                    'Asegúrese de tener una impresora configurada',
                    'Verifique que el navegador tiene permisos de impresión',
                    'Use la opción "Imprimir" del navegador si falla la impresión automática'
                ]
            ]
        ]
    ],
    
    'support' => [
        'title' => '15. Soporte Técnico',
        'content' => 'Si necesita asistencia adicional:',
        'items' => [
            '<strong>Contacto interno:</strong> Comuníquese con su administrador del sistema.',
            '<strong>Correo de soporte:</strong> Envíe un correo detallando el problema con capturas de pantalla.',
            '<strong>Sistema de tickets:</strong> Cree un ticket de soporte técnico desde el módulo correspondiente.',
            '<strong>Documentación:</strong> Consulte este manual y las guías específicas de cada módulo.'
        ]
    ]
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <?php if (isset($_GET['download']) && $_GET['download'] == '1'): ?>
    <script>
        window.onload = function() {
            document.getElementById('downloadBtn').style.display = 'none';
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
    <?php endif; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* PORTADA */
        .cover {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 80px 60px;
            text-align: center;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .cover h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .cover p {
            font-size: 20px;
            margin-bottom: 10px;
            opacity: 0.95;
        }
        
        .cover .version {
            font-size: 16px;
            opacity: 0.8;
            margin-top: 30px;
        }
        
        /* CONTENIDO */
        .content {
            padding: 60px;
        }
        
        .section {
            margin-bottom: 50px;
            page-break-inside: avoid;
        }
        
        h2 {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 15px 25px;
            margin: 30px 0 20px 0;
            font-size: 24px;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        h3 {
            color: #1e3a8a;
            font-size: 20px;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b82f6;
        }
        
        h4 {
            color: #3b82f6;
            font-size: 18px;
            margin: 20px 0 10px 0;
        }
        
        p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        ul, ol {
            margin: 15px 0 15px 30px;
        }
        
        li {
            margin-bottom: 10px;
            padding-left: 5px;
        }
        
        .steps {
            background: #f8f9fa;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .steps li {
            margin-bottom: 12px;
            line-height: 1.8;
        }
        
        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-style: italic;
        }
        
        .tip {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .feature-box {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        
        .feature-box ul {
            margin: 10px 0 0 20px;
        }
        
        .table-wrapper {
            overflow-x: auto;
            margin: 20px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        thead {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
        }
        
        thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #2563eb;
        }
        
        tbody tr {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        tbody tr:nth-child(even) {
            background: white;
        }
        
        tbody td {
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            color: #495057;
        }
        
        .problem-solution {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        
        .problem-solution h4 {
            color: #dc3545;
            margin-top: 0;
        }
        
        .footer {
            background: #4b5563;
            color: white;
            padding: 30px 60px;
            text-align: center;
            font-size: 14px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .section {
                page-break-inside: avoid;
            }
            
            h2 {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- PORTADA -->
        <div class="cover">
            <h1><?= htmlspecialchars($title) ?></h1>
            <p>Guía Completa de Usuario</p>
            <p>Sistema Integral de Gestión de Activos</p>
            <div class="version">
                <p>Versión <?= htmlspecialchars($version) ?> | <?= htmlspecialchars($date) ?></p>
                <p>© 2025 Amerimed Hospital | Powered by Arla</p>
            </div>
        </div>
        
        <!-- CONTENIDO -->
        <div class="content">
            
            <!-- Botón de descarga (solo visible cuando NO se está descargando) -->
            <?php if (!isset($_GET['download'])): ?>
            <div style="text-align: center; margin-bottom: 30px; padding: 20px; background: #e7f3ff; border-radius: 8px;">
                <h3 style="color: #1e3a8a; margin-bottom: 15px;">Descargar Manual en PDF</h3>
                <p style="margin-bottom: 15px;">Para guardar este manual como archivo PDF:</p>
                <button id="downloadBtn" onclick="window.print()" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; border: none; padding: 15px 40px; font-size: 16px; border-radius: 6px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <i style="margin-right: 8px;">📥</i> Descargar como PDF
                </button>
                <p style="margin-top: 15px; font-size: 14px; color: #666;">También puede usar <strong>Ctrl+P</strong> (Windows) o <strong>Cmd+P</strong> (Mac)</p>
            </div>
            <?php endif; ?>
            
            <!-- INTRODUCCIÓN -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['intro']['title']) ?></h2>
                <p><?= $manual['intro']['content'] ?></p>
                
                <div class="feature-box">
                    <h4>Funcionalidades Principales:</h4>
                    <ul>
                        <?php foreach ($manual['intro']['features'] as $feature): ?>
                            <li><?= $feature ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- ACCESO AL SISTEMA -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['access']['title']) ?></h2>
                <?php foreach ($manual['access']['sections'] as $section): ?>
                    <h3><?= htmlspecialchars($section['subtitle']) ?></h3>
                    
                    <?php if (isset($section['steps'])): ?>
                        <ol class="steps">
                            <?php foreach ($section['steps'] as $step): ?>
                                <li><?= $step ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                    
                    <?php if (isset($section['content'])): ?>
                        <p><?= $section['content'] ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($section['items'])): ?>
                        <ul>
                            <?php foreach ($section['items'] as $item): ?>
                                <li><?= $item ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if (isset($section['notes'])): ?>
                        <div class="note">
                            <strong>Nota:</strong> <?= $section['notes'] ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- DASHBOARD -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['dashboard']['title']) ?></h2>
                <p><?= $manual['dashboard']['content'] ?></p>
                
                <h4>Tarjetas de Resumen:</h4>
                <ul>
                    <?php foreach ($manual['dashboard']['cards'] as $card): ?>
                        <li><?= $card ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <h4>Secciones Visuales:</h4>
                <ul>
                    <?php foreach ($manual['dashboard']['sections'] as $section): ?>
                        <li><?= $section ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- GESTIÓN DE EQUIPOS -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['equipos']['title']) ?></h2>
                <?php foreach ($manual['equipos']['modules'] as $module): ?>
                    <h3><?= htmlspecialchars($module['subtitle']) ?></h3>
                    
                    <?php if (isset($module['content'])): ?>
                        <p><?= $module['content'] ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($module['steps'])): ?>
                        <ol class="steps">
                            <?php foreach ($module['steps'] as $step): ?>
                                <li><?= $step ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                    
                    <?php if (isset($module['features'])): ?>
                        <div class="feature-box">
                            <h4>Características:</h4>
                            <ul>
                                <?php foreach ($module['features'] as $feature): ?>
                                    <li><?= $feature ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($module['tips'])): ?>
                        <div class="tip">
                            <strong>Consejo:</strong> <?= $module['tips'] ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($module['notes'])): ?>
                        <div class="note">
                            <strong>Nota:</strong> <?= $module['notes'] ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- GESTIÓN DE PROVEEDORES -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['proveedores']['title']) ?></h2>
                <?php foreach ($manual['proveedores']['modules'] as $module): ?>
                    <h3><?= htmlspecialchars($module['subtitle']) ?></h3>
                    <ol class="steps">
                        <?php foreach ($module['steps'] as $step): ?>
                            <li><?= $step ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endforeach; ?>
            </div>
            
            <!-- GESTIÓN DE HERRAMIENTAS -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['herramientas']['title']) ?></h2>
                <?php foreach ($manual['herramientas']['modules'] as $module): ?>
                    <h3><?= htmlspecialchars($module['subtitle']) ?></h3>
                    <ol class="steps">
                        <?php foreach ($module['steps'] as $step): ?>
                            <li><?= $step ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endforeach; ?>
            </div>
            
            <!-- GESTIÓN DE ACCESORIOS -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['accesorios']['title']) ?></h2>
                <?php foreach ($manual['accesorios']['modules'] as $module): ?>
                    <h3><?= htmlspecialchars($module['subtitle']) ?></h3>
                    <ol class="steps">
                        <?php foreach ($module['steps'] as $step): ?>
                            <li><?= $step ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endforeach; ?>
            </div>
            
            <!-- GESTIÓN DE INVENTARIO -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['inventario']['title']) ?></h2>
                <p><?= $manual['inventario']['content'] ?></p>
                <?php foreach ($manual['inventario']['modules'] as $module): ?>
                    <h3><?= htmlspecialchars($module['subtitle']) ?></h3>
                    <ol class="steps">
                        <?php foreach ($module['steps'] as $step): ?>
                            <li><?= $step ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endforeach; ?>
            </div>
            
            <!-- CALENDARIO DE MANTENIMIENTOS -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['mantenimientos']['title']) ?></h2>
                <p><?= $manual['mantenimientos']['content'] ?></p>
                <ol class="steps">
                    <?php foreach ($manual['mantenimientos']['steps'] as $step): ?>
                        <li><?= $step ?></li>
                    <?php endforeach; ?>
                </ol>
                <div class="note">
                    <strong>Nota:</strong> <?= $manual['mantenimientos']['notes'] ?>
                </div>
            </div>
            
            <!-- SISTEMA DE TICKETS -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['tickets']['title']) ?></h2>
                <?php foreach ($manual['tickets']['modules'] as $module): ?>
                    <h3><?= htmlspecialchars($module['subtitle']) ?></h3>
                    <ol class="steps">
                        <?php foreach ($module['steps'] as $step): ?>
                            <li><?= $step ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endforeach; ?>
            </div>
            
            <!-- GENERACIÓN DE REPORTES -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['reportes']['title']) ?></h2>
                <p><?= $manual['reportes']['content'] ?></p>
                <ol class="steps">
                    <?php foreach ($manual['reportes']['steps'] as $step): ?>
                        <li><?= $step ?></li>
                    <?php endforeach; ?>
                </ol>
                <div class="note">
                    <strong>Nota:</strong> <?= $manual['reportes']['notes'] ?>
                </div>
            </div>
            
            <!-- CONFIGURACIÓN DEL SISTEMA -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['admin']['title']) ?></h2>
                <p><?= $manual['admin']['content'] ?></p>
                <?php foreach ($manual['admin']['modules'] as $module): ?>
                    <h3><?= htmlspecialchars($module['subtitle']) ?></h3>
                    
                    <?php if (isset($module['content'])): ?>
                        <p><?= $module['content'] ?></p>
                    <?php endif; ?>
                    
                    <ol class="steps">
                        <?php foreach ($module['steps'] as $step): ?>
                            <li><?= $step ?></li>
                        <?php endforeach; ?>
                    </ol>
                    
                    <?php if (isset($module['notes'])): ?>
                        <div class="note">
                            <strong>Nota:</strong> <?= $module['notes'] ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- CONSEJOS Y BUENAS PRÁCTICAS -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['tips']['title']) ?></h2>
                <ul class="steps">
                    <?php foreach ($manual['tips']['items'] as $item): ?>
                        <li><?= $item ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- SOLUCIÓN DE PROBLEMAS -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['troubleshooting']['title']) ?></h2>
                <?php foreach ($manual['troubleshooting']['issues'] as $issue): ?>
                    <div class="problem-solution">
                        <h4>Problema: <?= htmlspecialchars($issue['problem']) ?></h4>
                        <p><strong>Soluciones:</strong></p>
                        <ul>
                            <?php foreach ($issue['solutions'] as $solution): ?>
                                <li><?= $solution ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- SOPORTE TÉCNICO -->
            <div class="section">
                <h2><?= htmlspecialchars($manual['support']['title']) ?></h2>
                <p><?= $manual['support']['content'] ?></p>
                <ul>
                    <?php foreach ($manual['support']['items'] as $item): ?>
                        <li><?= $item ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
        </div>
        
        <!-- PIE DE PÁGINA -->
        <div class="footer">
            <p><strong><?= htmlspecialchars($title) ?></strong></p>
            <p>Versión <?= htmlspecialchars($version) ?> | <?= htmlspecialchars($date) ?></p>
            <p>© 2025 Amerimed Hospital | Todos los derechos reservados | Powered by Arla</p>
            <p style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
                Este documento es confidencial y está destinado únicamente para el personal autorizado de la organización.
            </p>
        </div>
    </div>
    
    <script>
        // Auto-imprimir al cargar (opcional)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>

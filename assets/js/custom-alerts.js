/**
 * Sistema de Alertas Moderno y Minimalista
 * Alertas centradas en pantalla con animaciones suaves
 * @version 1.0
 */

(function() {
    'use strict';
    
    // Configuración por tipo de alerta
    const alertConfig = {
        success: {
            icon: '<i class="fas fa-check-circle"></i>',
            title: 'Éxito'
        },
        error: {
            icon: '<i class="fas fa-times-circle"></i>',
            title: 'Error'
        },
        warning: {
            icon: '<i class="fas fa-exclamation-triangle"></i>',
            title: 'Advertencia'
        },
        info: {
            icon: '<i class="fas fa-info-circle"></i>',
            title: 'Información'
        }
    };
    
    /**
     * Muestra una alerta moderna centrada en pantalla
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo de alerta: success, error, warning, info
     * @param {number} duration - Duración en ms (0 = manual)
     */
    window.alert_toast = function(message, type = 'info', duration = 3000) {
        // Validar tipo
        if (!alertConfig[type]) {
            type = 'info';
        }
        
        const config = alertConfig[type];
        
        // Crear overlay
        const overlay = document.createElement('div');
        overlay.className = 'alert-overlay';
        
        // Crear alerta
        const alert = document.createElement('div');
        alert.className = 'custom-alert';
        alert.innerHTML = `
            <div class="custom-alert-icon ${type}">
                ${config.icon}
            </div>
            <div class="custom-alert-title">${config.title}</div>
            <div class="custom-alert-message">${message}</div>
            <button class="custom-alert-close">Aceptar</button>
            ${duration > 0 ? '<div class="custom-alert-progress"></div>' : ''}
        `;
        
        overlay.appendChild(alert);
        document.body.appendChild(overlay);
        
        // Función para cerrar la alerta
        const closeAlert = () => {
            overlay.classList.add('closing');
            setTimeout(() => {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 300);
        };
        
        // Evento del botón cerrar
        const closeBtn = alert.querySelector('.custom-alert-close');
        closeBtn.addEventListener('click', closeAlert);
        
        // Cerrar al hacer clic en el overlay (opcional)
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeAlert();
            }
        });
        
        // Auto-cerrar después de la duración especificada
        if (duration > 0) {
            setTimeout(closeAlert, duration);
        }
        
        // Prevenir scroll del body
        document.body.style.overflow = 'hidden';
        
        // Restaurar scroll al cerrar
        overlay.addEventListener('animationend', () => {
            if (overlay.classList.contains('closing')) {
                document.body.style.overflow = '';
            }
        });
    };
    
    /**
     * Alias para compatibilidad con código legacy
     */
    window.showAlert = window.alert_toast;
    
    /**
     * Confirmación moderna (opcional)
     * @param {string} message - Mensaje de confirmación
     * @param {function} onConfirm - Callback al confirmar
     * @param {function} onCancel - Callback al cancelar
     */
    window.confirm_toast = function(message, onConfirm, onCancel) {
        const overlay = document.createElement('div');
        overlay.className = 'alert-overlay';
        
        const alert = document.createElement('div');
        alert.className = 'custom-alert';
        alert.innerHTML = `
            <div class="custom-alert-icon warning">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="custom-alert-title">Confirmación</div>
            <div class="custom-alert-message">${message}</div>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button class="custom-alert-close" data-action="cancel" 
                        style="background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);">
                    Cancelar
                </button>
                <button class="custom-alert-close" data-action="confirm">
                    Confirmar
                </button>
            </div>
        `;
        
        overlay.appendChild(alert);
        document.body.appendChild(overlay);
        
        const closeAlert = () => {
            overlay.classList.add('closing');
            setTimeout(() => {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
                document.body.style.overflow = '';
            }, 300);
        };
        
        alert.querySelector('[data-action="confirm"]').addEventListener('click', () => {
            closeAlert();
            if (typeof onConfirm === 'function') onConfirm();
        });
        
        alert.querySelector('[data-action="cancel"]').addEventListener('click', () => {
            closeAlert();
            if (typeof onCancel === 'function') onCancel();
        });
        
        document.body.style.overflow = 'hidden';
    };
    
    console.log('✓ Sistema de alertas moderno cargado correctamente');
})();

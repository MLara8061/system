/**
 * Sistema de notificaciones personalizado
 * Reemplaza las alertas nativas con componentes visuales mejorados
 */

class NotificationManager {
    constructor() {
        this.createContainer();
    }

    createContainer() {
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }
    }

    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        const uniqueId = 'notif-' + Date.now();
        notification.id = uniqueId;

        // Estilos base
        const baseStyles = {
            'padding': '16px 20px',
            'margin-bottom': '10px',
            'border-radius': '6px',
            'font-size': '14px',
            'font-weight': '500',
            'box-shadow': '0 4px 12px rgba(0, 0, 0, 0.15)',
            'pointer-events': 'auto',
            'display': 'flex',
            'align-items': 'center',
            'gap': '12px',
            'animation': 'slideInRight 0.3s ease-out',
            'backdrop-filter': 'blur(10px)'
        };

        // Estilos por tipo
        const typeStyles = {
            'success': {
                'background': 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                'color': 'white',
                'icon': '✓'
            },
            'error': {
                'background': 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                'color': 'white',
                'icon': '✕'
            },
            'warning': {
                'background': 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                'color': 'white',
                'icon': '⚠'
            },
            'info': {
                'background': 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                'color': 'white',
                'icon': 'ℹ'
            }
        };

        const style = typeStyles[type] || typeStyles['info'];
        
        // Aplicar estilos
        Object.assign(notification.style, baseStyles);
        Object.assign(notification.style, {
            'background': style.background,
            'color': style.color
        });

        // HTML
        notification.innerHTML = `
            <span style="font-size: 18px; font-weight: bold; min-width: 24px; text-align: center;">
                ${style.icon}
            </span>
            <span style="flex: 1;">${message}</span>
            <button style="
                background: rgba(255,255,255,0.2);
                border: none;
                color: inherit;
                cursor: pointer;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 18px;
                line-height: 1;
                transition: background 0.2s;
            " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ×
            </button>
        `;

        // Event listener para cerrar
        notification.querySelector('button').addEventListener('click', () => {
            this.remove(uniqueId);
        });

        // Agregar al contenedor
        const container = document.getElementById('notification-container');
        container.appendChild(notification);

        // Auto-remove
        if (duration > 0) {
            setTimeout(() => this.remove(uniqueId), duration);
        }

        return notification;
    }

    remove(id) {
        const notif = document.getElementById(id);
        if (notif) {
            notif.style.animation = 'slideOutRight 0.3s ease-out forwards';
            setTimeout(() => notif.remove(), 300);
        }
    }

    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 5000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }
}

// Crear instancia global
const notification = new NotificationManager();

// Agregar estilos de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    #notification-container {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }
`;
document.head.appendChild(style);

// Reemplazar alert nativo (opcional)
// window.alert = function(message) {
//     notification.info(message);
// };

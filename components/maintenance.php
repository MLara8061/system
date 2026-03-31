<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema en Mantenimiento - Actualizaciones en Progreso</title>
    <link rel="stylesheet" href="../assets/plugins/fontawesome/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #000;
            color: #0f0;
            overflow: hidden;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Efecto Matrix Canvas */
        #matrix-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.15;
        }

        /* Contenedor principal */
        .maintenance-container {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 2rem;
            max-width: 700px;
            width: 90%;
            background: rgba(0, 0, 0, 0.85);
            border: 2px solid #0f0;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.5);
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { box-shadow: 0 0 20px rgba(0, 255, 0, 0.4); }
            to { box-shadow: 0 0 40px rgba(0, 255, 0, 0.8); }
        }

        .logo {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 0 10px #0f0;
        }

        .subtitle {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.8;
        }

        /* Barra de progreso */
        .progress-container {
            margin: 2rem 0;
            background: rgba(0, 50, 0, 0.5);
            border: 1px solid #0f0;
            border-radius: 20px;
            height: 30px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #0a0, #0f0);
            width: 0%;
            transition: width 1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 0 10px #0f0;
        }

        .progress-text {
            position: absolute;
            width: 100%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            z-index: 1;
            text-shadow: 1px 1px 2px #000;
        }

        /* Información de tiempo */
        .time-info {
            margin-top: 1.5rem;
            font-size: 1rem;
            opacity: 0.9;
        }

        .time-info span {
            display: inline-block;
            margin: 0 10px;
            padding: 5px 15px;
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #0f0;
            border-radius: 5px;
        }

        /* Contador regresivo */
        .countdown {
            margin-top: 2rem;
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .countdown-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .countdown-value {
            font-size: 2.5rem;
            background: rgba(0, 255, 0, 0.1);
            border: 2px solid #0f0;
            border-radius: 8px;
            padding: 10px 20px;
            min-width: 80px;
            text-shadow: 0 0 10px #0f0;
        }

        .countdown-label {
            font-size: 0.8rem;
            margin-top: 5px;
            opacity: 0.7;
            text-transform: uppercase;
        }

        /* Status dots */
        .status-dots {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .dot {
            width: 12px;
            height: 12px;
            background: #0f0;
            border-radius: 50%;
            animation: blink 1.5s infinite;
        }

        .dot:nth-child(2) { animation-delay: 0.3s; }
        .dot:nth-child(3) { animation-delay: 0.6s; }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.2; }
        }

        /* Responsive */
        @media (max-width: 600px) {
            h1 { font-size: 1.5rem; }
            .subtitle { font-size: 0.9rem; }
            .countdown-value { font-size: 1.8rem; min-width: 60px; padding: 8px 15px; }
            .countdown-label { font-size: 0.7rem; }
        }
    </style>
</head>
<body>
    <!-- Canvas para efecto Matrix -->
    <canvas id="matrix-canvas"></canvas>

    <!-- Contenedor principal -->
    <div class="maintenance-container">
        <div class="logo">
            <i class="fas fa-cogs"></i>
        </div>
        
        <h1>
            <i class="fas fa-sync fa-spin"></i>
            Cargando Nuevas Actualizaciones
        </h1>
        
        <p class="subtitle">
            El sistema estará listo pronto
        </p>

        <!-- Barra de progreso -->
        <div class="progress-container">
            <div class="progress-text" id="progress-text">0%</div>
            <div class="progress-bar" id="progress-bar"></div>
        </div>

        <!-- Información de tiempo -->
        <div class="time-info">
            <span id="current-time"></span>
            <span>Fin estimado: Lunes 16 Dic, 8:00 AM</span>
        </div>

        <!-- Contador regresivo -->
        <div class="countdown">
            <div class="countdown-item">
                <div class="countdown-value" id="days">00</div>
                <div class="countdown-label">Días</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-value" id="hours">00</div>
                <div class="countdown-label">Horas</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-value" id="minutes">00</div>
                <div class="countdown-label">Minutos</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-value" id="seconds">00</div>
                <div class="countdown-label">Segundos</div>
            </div>
        </div>

        <!-- Dots animados -->
        <div class="status-dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <script>
        // ==========================================
        // CONFIGURACIÓN DE FECHAS
        // ==========================================
        const startDate = new Date('2025-12-13T20:00:00'); // Viernes 13 dic, 8pm
        const endDate = new Date('2025-12-16T08:00:00');   // Lunes 16 dic, 8am
        
        // ==========================================
        // EFECTO MATRIX EN CANVAS
        // ==========================================
        const canvas = document.getElementById('matrix-canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*()';
        const fontSize = 14;
        const columns = canvas.width / fontSize;
        const drops = Array(Math.floor(columns)).fill(1);
        
        function drawMatrix() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#0f0';
            ctx.font = fontSize + 'px monospace';
            
            for (let i = 0; i < drops.length; i++) {
                const text = chars[Math.floor(Math.random() * chars.length)];
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);
                
                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }
        
        setInterval(drawMatrix, 50);
        
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
        
        // ==========================================
        // ACTUALIZAR PROGRESO Y COUNTDOWN
        // ==========================================
        function updateProgress() {
            const now = new Date();
            
            // Calcular progreso total (0-100%)
            const totalDuration = endDate - startDate;
            const elapsed = now - startDate;
            const progress = Math.min(Math.max((elapsed / totalDuration) * 100, 0), 100);
            
            // Actualizar barra de progreso
            document.getElementById('progress-bar').style.width = progress.toFixed(2) + '%';
            document.getElementById('progress-text').textContent = progress.toFixed(2) + '%';
            
            // Calcular tiempo restante
            const timeLeft = endDate - now;
            
            if (timeLeft <= 0) {
                // Mantenimiento completado
                document.getElementById('days').textContent = '00';
                document.getElementById('hours').textContent = '00';
                document.getElementById('minutes').textContent = '00';
                document.getElementById('seconds').textContent = '00';
                
                // Redirigir al sistema
                setTimeout(() => {
                    window.location.href = '/index.php';
                }, 2000);
                return;
            }
            
            const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            document.getElementById('days').textContent = String(days).padStart(2, '0');
            document.getElementById('hours').textContent = String(hours).padStart(2, '0');
            document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
            document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
            
            // Actualizar hora actual
            document.getElementById('current-time').textContent = now.toLocaleString('es-ES', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Actualizar cada segundo
        updateProgress();
        setInterval(updateProgress, 1000);
    </script>
</body>
</html>

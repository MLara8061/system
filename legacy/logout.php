<?php
require_once 'config/session.php';

// Destruir sesión de forma segura
destroy_session();

// Redirigir al login
header("location: login.php");
exit;


<?php
/**
 * Helper para resolver rutas dinámicamente según la ubicación del archivo
 */

class PathResolver {
    private static $basePath = null;
    
    /**
     * Obtener la ruta base del proyecto (raíz)
     */
    public static function getBasePath() {
        if (self::$basePath === null) {
            self::$basePath = dirname(dirname(dirname(__FILE__)));
        }
        return self::$basePath;
    }
    
    /**
     * Obtener ruta relativa desde raíz (para <link>, <script>, <img>)
     * Uso: PathResolver::asset('assets/css/style.css')
     */
    public static function asset($path) {
        return '/' . ltrim($path, '/');
    }
    
    /**
     * Obtener ruta absoluta en servidor
     * Uso: PathResolver::path('public/assets')
     */
    public static function path($path) {
        return self::getBasePath() . '/' . ltrim($path, '/');
    }
    
    /**
     * Obtener ruta absoluta relativa a un archivo actual
     */
    public static function relative($from, $to) {
        $from = realpath($from);
        $to = realpath($to);
        
        if ($from === $to) return '.';
        
        $fromParts = array_filter(explode(DIRECTORY_SEPARATOR, $from), 'strlen');
        $toParts = array_filter(explode(DIRECTORY_SEPARATOR, $to), 'strlen');
        
        $common = 0;
        foreach ($fromParts as $i => $part) {
            if (isset($toParts[$i]) && $part === $toParts[$i]) {
                $common++;
            } else {
                break;
            }
        }
        
        $ups = count($fromParts) - $common;
        $downs = array_slice($toParts, $common);
        
        $path = str_repeat('..', $ups);
        if ($path && $downs) $path .= DIRECTORY_SEPARATOR;
        $path .= implode(DIRECTORY_SEPARATOR, $downs);
        
        return $path;
    }
}

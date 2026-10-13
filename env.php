<?php
/**
 * MYG - Parser nativo de variables de entorno (.env)
 * Carga configuraciones sin dependencias externas hacia $_ENV y $_SERVER
 */

if (!function_exists('cargarEnv')) {
    function cargarEnv($rutaArchivo = null) {
        if ($rutaArchivo === null) {
            $rutaArchivo = __DIR__ . '/.env';
        }

        if (!file_exists($rutaArchivo) || !is_readable($rutaArchivo)) {
            return false;
        }

        $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            return false;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            // Ignorar comentarios que comiencen con # o //
            if ($linea === '' || str_starts_with($linea, '#') || str_starts_with($linea, '//')) {
                continue;
            }

            // Separar clave y valor en el primer '='
            $posIgual = strpos($linea, '=');
            if ($posIgual === false) {
                continue;
            }

            $clave = trim(substr($linea, 0, $posIgual));
            $valor = trim(substr($linea, $posIgual + 1));

            // Si el valor contiene comentarios al final sin comillas, limpiar
            if (!empty($valor)) {
                // Quitar comillas simples o dobles envolventes
                if (
                    (str_starts_with($valor, '"') && str_ends_with($valor, '"')) ||
                    (str_starts_with($valor, "'") && str_ends_with($valor, "'"))
                ) {
                    $valor = substr($valor, 1, -1);
                } else {
                    // Si no está entre comillas y tiene un comentario con #
                    $posComentario = strpos($valor, '#');
                    if ($posComentario !== false) {
                        $valor = trim(substr($valor, 0, $posComentario));
                    }
                }
            }

            // No sobrescribir variables ya definidas por Docker / el sistema
            $desdeSistema = getenv($clave);
            if ($desdeSistema !== false && $desdeSistema !== '') {
                $valorParseado = parseEnvValor($desdeSistema);
                $_ENV[$clave] = $valorParseado;
                $_SERVER[$clave] = $valorParseado;
                continue;
            }

            $valorParseado = parseEnvValor($valor);

            // Asignar a $_ENV, $_SERVER y putenv
            $_ENV[$clave] = $valorParseado;
            $_SERVER[$clave] = $valorParseado;
            putenv("{$clave}={$valor}");
        }

        return true;
    }
}

if (!function_exists('parseEnvValor')) {
    function parseEnvValor($valor) {
        $valorLower = strtolower((string)$valor);
        if ($valorLower === 'true') {
            return true;
        }
        if ($valorLower === 'false') {
            return false;
        }
        if ($valorLower === 'null') {
            return null;
        }
        return $valor;
    }
}

if (!function_exists('sincronizarEnvSistema')) {
    function sincronizarEnvSistema(array $claves) {
        foreach ($claves as $clave) {
            $desdeSistema = getenv($clave);
            if ($desdeSistema === false || $desdeSistema === '') {
                continue;
            }
            $valorParseado = parseEnvValor($desdeSistema);
            $_ENV[$clave] = $valorParseado;
            $_SERVER[$clave] = $valorParseado;
        }
    }
}

// Docker / hosting: tomar primero variables del entorno del proceso
sincronizarEnvSistema([
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
    'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL', 'SESSION_NAME',
]);

// Luego completar con archivo .env si existe (sin pisar las del sistema)
cargarEnv();


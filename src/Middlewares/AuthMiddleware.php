<?php

namespace App\Middlewares;

class AuthMiddleware
{
    public function handle()
    {
        // Aquí iría la lógica de autenticación
        // Por ejemplo, verificar si existe una sesión de usuario válida

        if (!isset($_SESSION['user_id'])) {
            header("HTTP/1.0 401 Unauthorized");
            echo "Acceso no autorizado.";
            return false; // Detiene la ejecución de la ruta
        }

        return true; // Permite que la petición continúe
    }
}
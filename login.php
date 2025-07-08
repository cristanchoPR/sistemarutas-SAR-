<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';

// Si ya está logueado, redirigir según el tipo de usuario
if (isset($_SESSION['user'])) {
    header('Location: ' . ($_SESSION['user']['tipo_usuario'] === 'admin' ? 'admin.php' : 'lider.php'));
    exit();
}

$auth = new Auth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $result = $auth->login($_POST['email'], $_POST['password']);
            if ($result['success']) {
                header('Location: ' . ($_SESSION['user']['tipo_usuario'] === 'admin' ? 'admin.php' : 'lider.php'));
                exit();
            } else {
                $error = $result['message'];
            }
        } else if ($_POST['action'] === 'register') {
            $result = $auth->register(
                $_POST['nombre'],
                $_POST['email'],
                $_POST['password'],
                'lider' // Solo permitir registro de líderes
            );
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/Imagen1.png">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            margin: auto;
        }
        .login-header {
            background: #2c3e50;
            color: white;
            padding: 25px 20px;
            text-align: center;
        }
        .login-header h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .login-header p {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
        }
        .login-body {
            padding: 30px;
        }
        .nav-tabs {
            border: none;
            margin-bottom: 25px;
            justify-content: center;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 12px 25px;
            border-radius: 25px;
            margin: 0 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .nav-tabs .nav-link:hover {
            background: #f8f9fa;
        }
        .nav-tabs .nav-link.active {
            background: #2c3e50;
            color: white;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        .btn-primary {
            background: #2c3e50;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #34495e;
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 12px 15px;
        }
        .tab-content {
            padding: 0 5px;
        }
        @media (max-width: 576px) {
            .login-container {
                margin: 10px;
            }
            .login-body {
                padding: 20px;
            }
            .nav-tabs .nav-link {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>SAR</h2>
            <p>Sistema de Administración de Rutas</p>
        </div>
        <div class="login-body">
            <?php if($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <ul class="nav nav-tabs" id="loginTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                        <i class="fas fa-user-plus me-2"></i>Registrarse
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="loginTabsContent">
                <!-- Login Form -->
                <div class="tab-pane fade show active" id="login" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="Ingrese su email">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required 
                                   placeholder="Ingrese su contraseña">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                        </button>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="tab-pane fade" id="register" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="action" value="register">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                   placeholder="Ingrese su nombre completo">
                        </div>
                        <div class="mb-3">
                            <label for="register-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="register-email" name="email" required 
                                   placeholder="Ingrese su email">
                        </div>
                        <div class="mb-3">
                            <label for="register-password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="register-password" name="password" required 
                                   placeholder="Ingrese su contraseña">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>Registrarse
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevenir el comportamiento por defecto del botón retroceder
        window.addEventListener('popstate', function(event) {
            // Mostrar confirmación de cierre de sesión
            if (confirm('¿Desea cerrar sesión?')) {
                // Si confirma, redirigir a logout.php
                window.location.href = 'logout.php';
            } else {
                // Si cancela, volver a la página actual
                history.pushState(null, '', window.location.href);
            }
        });

        // Agregar estado inicial al historial
        history.pushState(null, '', window.location.href);
    </script>
</body>
</html> 
<?php
session_start();

// Archivo para almacenar usuarios
$usersFile = 'usuarios.json';

// Crear archivo de usuarios si no existe con usuario demo
if (!file_exists($usersFile)) {
    $defaultUsers = [
        [
            'id' => 1,
            'nombre' => 'Administrador',
            'email' => 'admin@lbaumg.edu.gt',
            'password' => password_hash('admin123', PASSWORD_DEFAULT)
        ]
    ];
    file_put_contents($usersFile, json_encode($defaultUsers, JSON_PRETTY_PRINT));
}

// Funciones auxiliares
function getUsers() {
    global $usersFile;
    $content = file_get_contents($usersFile);
    return json_decode($content, true) ?: [];
}

function saveUsers($users) {
    global $usersFile;
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

function findUserByEmail($email) {
    $users = getUsers();
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    return null;
}

function getNextId() {
    $users = getUsers();
    if (empty($users)) return 1;
    $maxId = max(array_column($users, 'id'));
    return $maxId + 1;
}

// Procesar acciones
$action = $_GET['action'] ?? '';
$message = '';

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    $user = findUserByEmail($email);
    
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nombre'] = $user['nombre'];
        header('Location: default.php?action=dashboard');
        exit;
    } else {
        $message = 'Credenciales incorrectas';
    }
}

// REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    
    if (findUserByEmail($email)) {
        $message = 'El correo ya está registrado';
    } else {
        $users = getUsers();
        $users[] = [
            'id' => getNextId(),
            'nombre' => $nombre,
            'email' => $email,
            'password' => password_hash($pass, PASSWORD_DEFAULT)
        ];
        saveUsers($users);
        $message = 'Usuario registrado exitosamente';
    }
}

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if (findUserByEmail($email)) {
        $message = 'El correo ya existe';
    } else {
        $users = getUsers();
        $users[] = [
            'id' => getNextId(),
            'nombre' => $nombre,
            'email' => $email,
            'password' => password_hash($pass, PASSWORD_DEFAULT)
        ];
        saveUsers($users);
        $message = 'Usuario creado exitosamente';
    }
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    
    $users = getUsers();
    foreach ($users as &$user) {
        if ($user['id'] === $id) {
            $user['nombre'] = $nombre;
            $user['email'] = $email;
            break;
        }
    }
    saveUsers($users);
    $message = 'Usuario actualizado exitosamente';
}

// DELETE
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $users = getUsers();
    $users = array_filter($users, function($user) use ($id) {
        return $user['id'] !== $id;
    });
    saveUsers(array_values($users));
    header('Location: default.php?action=dashboard');
    exit;
}

// LOGOUT
if ($action === 'logout') {
    session_destroy();
    header('Location: default.php');
    exit;
}

// Obtener usuario para editar
$editUser = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $users = getUsers();
    foreach ($users as $user) {
        if ($user['id'] === $id) {
            $editUser = $user;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LBA UMG - Sistema de Gestión de Usuarios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a7b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: #1a1a1a;
            border-radius: 16px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .dashboard-container {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            max-width: 1200px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid #fff;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }
        
        .logo-text {
            color: #fff;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        h1 {
            color: #fff;
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            color: #999;
            text-align: center;
            margin-bottom: 32px;
            font-size: 14px;
            font-weight: 400;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #fff;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #2196F3;
            background: #333;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }
        
        input::placeholder {
            color: #666;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: #2196F3;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        
        .btn:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .register-link {
            text-align: center;
            margin-top: 24px;
            color: #999;
            font-size: 14px;
        }
        
        .register-link a {
            color: #2196F3;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: #1976D2;
            text-decoration: underline;
        }
        
        .demo-info {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #333;
            color: #666;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
        }
        
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            width: auto;
            margin: 0;
            border-radius: 6px;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        
        .btn-edit:hover {
            background: #e0a800;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.4);
        }
        
        .btn-delete {
            background: #dc3545;
        }
        
        .btn-delete:hover {
            background: #c82333;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
        }
        
        .btn-logout {
            background: #6c757d;
            margin-bottom: 20px;
        }
        
        .btn-logout:hover {
            background: #5a6268;
            box-shadow: 0 2px 8px rgba(108, 117, 125, 0.4);
        }
        
        .btn-cancel {
            background: #6c757d;
            margin-left: 10px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .dashboard-header h2 {
            color: #333;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        .user-info {
            color: #666;
            font-size: 14px;
        }
        
        .user-info strong {
            color: #333;
            font-weight: 600;
        }
        
        .form-inline {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 12px;
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            align-items: end;
        }
        
        .form-inline input {
            background: #fff;
            border: 1px solid #ddd;
            color: #333;
        }
        
        .form-inline input:focus {
            border-color: #2196F3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }
        
        .form-inline label {
            color: #333;
            font-size: 13px;
        }
        
        .form-title {
            grid-column: 1 / -1;
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            .form-inline {
                grid-template-columns: 1fr;
            }
            
            .form-title {
                grid-column: 1;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .container, .dashboard-container {
                padding: 24px;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['user_id']) && $action !== 'register'): ?>
        <!-- LOGIN FORM -->
        <div class="container">
            <div class="logo">
                <div class="logo-text">UMG</div>
            </div>
            <h1>LBA UMG</h1>
            <p class="subtitle">Sistema de Gestión de Usuarios</p>
            
            <?php if ($message): ?>
                <div class="message <?= strpos($message, 'Error') !== false || strpos($message, 'incorrectas') !== false ? 'error' : '' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" placeholder="usuario@lbaumg.edu.gt" required>
                </div>
                
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" name="login" class="btn">Iniciar Sesión</button>
            </form>
            
            <div class="register-link">
                ¿No tienes cuenta? <a href="?action=register">Regístrate</a>
            </div>
            
        </div>
        
    <?php elseif ($action === 'register'): ?>
        <!-- REGISTER FORM -->
        <div class="container">
            <div class="logo">
                <div class="logo-text">UMG</div>
            </div>
            <h1>Registro</h1>
            <p class="subtitle">Crear nueva cuenta</p>
            
            <?php if ($message): ?>
                <div class="message <?= strpos($message, 'Error') !== false || strpos($message, 'ya está') !== false ? 'error' : '' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre" placeholder="Juan Pérez" required>
                </div>
                
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" placeholder="usuario@lbaumg.edu.gt" required>
                </div>
                
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" name="register" class="btn">Registrarse</button>
            </form>
            
            <div class="register-link">
                ¿Ya tienes cuenta? <a href="default.php">Iniciar Sesión</a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- DASHBOARD CRUD -->
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2>Panel de Administración</h2>
                <div class="user-info">
                    Bienvenido: <strong><?= htmlspecialchars($_SESSION['user_nombre']) ?></strong>
                </div>
            </div>
            
            <a href="?action=logout"><button class="btn btn-logout">Cerrar Sesión</button></a>
            
            <?php if ($message): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <!-- CREATE/UPDATE FORM -->
            <form method="POST" class="form-inline">
                <h3 class="form-title"><?= $editUser ? 'Editar Usuario' : 'Crear Nuevo Usuario' ?></h3>
                
                <?php if ($editUser): ?>
                    <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" value="<?= $editUser['nombre'] ?? '' ?>" placeholder="Nombre completo" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= $editUser['email'] ?? '' ?>" placeholder="correo@ejemplo.com" required>
                </div>
                
                <?php if (!$editUser): ?>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" name="<?= $editUser ? 'update' : 'create' ?>" class="btn btn-small">
                        <?= $editUser ? 'Actualizar' : 'Crear' ?>
                    </button>
                    <?php if ($editUser): ?>
                        <a href="default.php?action=dashboard"><button type="button" class="btn btn-small btn-cancel">Cancelar</button></a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- READ TABLE -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $usuarios = getUsers();
                    foreach ($usuarios as $usuario): 
                    ?>
                    <tr>
                        <td><?= $usuario['id'] ?></td>
                        <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td class="actions">
                            <a href="?action=edit&id=<?= $usuario['id'] ?>">
                                <button class="btn btn-small btn-edit">Editar</button>
                            </a>
                            <a href="?action=delete&id=<?= $usuario['id'] ?>" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                <button class="btn btn-small btn-delete">Eliminar</button>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</body>
</html>
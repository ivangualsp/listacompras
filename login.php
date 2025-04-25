<?php
require_once 'config.php';
iniciarSessao();

$erro = '';

// Verificar se usuário já está logado
if (estaLogado()) {
    header('Location: dashboard.php');
    exit;
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        // Buscar usuário pelo email
        $result = supabaseRequest("users?email=eq.$email", 'GET');
        
        if ($result['statusCode'] === 200 && !empty($result['body'])) {
            $usuario = $result['body'][0];
            
            // Verificar senha
            if ($email === 'admin@mediaindoor.com' && $senha === 'admin123') {
                // Login manual para admin
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['name'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_imagem'] = $usuario['profile_image'] ?? 'https://randomuser.me/api/portraits/men/32.jpg';
                
                header('Location: dashboard.php');
                exit;
            }
            // Verificar senha normal
            elseif (verificarSenha($senha, $usuario['password'])) {
                // Login bem sucedido
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['name'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_imagem'] = $usuario['profile_image'] ?? 'https://randomuser.me/api/portraits/men/32.jpg';
                
                header('Location: dashboard.php');
                exit;
            } else {
                $erro = 'Senha incorreta. Por favor, tente novamente ou use redefinir_senha.php para criar/redefinir a senha.';
            }
        } else {
            $erro = 'Usuário não encontrado. Por favor, verifique o e-mail informado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .media-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .dashboard-card:hover {
            transform: scale(1.02);
        }
        .screen-preview {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .content-tab.active {
            border-bottom: 3px solid #3b82f6;
            color: #3b82f6;
        }
        .marquee {
            animation: marquee 20s linear infinite;
            white-space: nowrap;
        }
        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
        <div class="text-center mb-6">
            <div class="flex items-center justify-center space-x-2 mb-2">
                <i class="fas fa-tv text-blue-500 text-3xl"></i>
                <span class="text-2xl font-bold text-gray-800">Media<span class="text-blue-500">Indoor</span></span>
            </div>
            <h1 class="text-xl font-semibold text-gray-700">Acesso ao Sistema</h1>
        </div>
        
        <?php if (!empty($erro)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $erro; ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">E-mail</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </div>
                    <input type="email" id="email" name="email" class="pl-10 w-full border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="seu@email.com" required>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="senha" class="block text-gray-700 text-sm font-medium mb-2">Senha</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="senha" name="senha" class="pl-10 w-full border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Sua senha" required>
                </div>
            </div>
            
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <input id="lembrar" name="lembrar" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="lembrar" class="ml-2 block text-sm text-gray-700">Lembrar</label>
                </div>
                <a href="redefinir_senha.php" class="text-sm text-blue-600 hover:text-blue-800">Esqueceu a senha?</a>
            </div>
            
            <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                Entrar
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Acesse com as credenciais fornecidas pelo administrador</p>
            <p class="mt-2">Usuário padrão: <strong>admin@mediaindoor.com</strong> / Senha: <strong>admin123</strong></p>
            <p class="mt-2 text-xs">Se estiver com problemas de login, acesse <a href="redefinir_senha.php" class="text-blue-600">redefinir_senha.php</a> para reiniciar a senha do administrador.</p>
        </div>
    </div>
</body>
</html> 
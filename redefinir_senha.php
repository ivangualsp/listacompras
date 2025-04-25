<?php
require_once 'config.php';

// Este é um script de uso único para redefinir a senha do administrador
// Após o uso, remova este arquivo do servidor por segurança

// Verificar se o usuário admin existe
$admin_check = supabaseRequest("users?email=eq.admin@mediaindoor.com", 'GET');

if ($admin_check['statusCode'] === 200) {
    if (empty($admin_check['body'])) {
        // O usuário admin não existe, vamos criá-lo
        $admin_data = [
            'name' => 'Administrador',
            'email' => 'admin@mediaindoor.com',
            'password' => hashSenha('admin123'), // Hash da senha
            'profile_image' => 'https://randomuser.me/api/portraits/men/32.jpg',
            'role' => 'admin'
        ];
        
        $result = supabaseRequest('users', 'POST', $admin_data);
        
        if ($result['statusCode'] === 201) {
            echo "<p>Usuário administrador criado com sucesso!</p>";
            echo "<p>Email: admin@mediaindoor.com</p>";
            echo "<p>Senha: admin123</p>";
        } else {
            echo "<p>Erro ao criar usuário administrador.</p>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        }
    } else {
        // O usuário admin existe, vamos redefinir a senha
        $admin_id = $admin_check['body'][0]['id'];
        $senha_hash = hashSenha('admin123');
        
        $update_data = [
            'password' => $senha_hash
        ];
        
        $result = supabaseRequest("users?id=eq.$admin_id", 'PATCH', $update_data);
        
        if ($result['statusCode'] === 200) {
            echo "<p>Senha do administrador redefinida com sucesso!</p>";
            echo "<p>Email: admin@mediaindoor.com</p>";
            echo "<p>Senha: admin123</p>";
        } else {
            echo "<p>Erro ao redefinir senha do administrador.</p>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        }
    }
} else {
    echo "<p>Erro ao verificar usuário administrador.</p>";
    echo "<pre>" . print_r($admin_check, true) . "</pre>";
}

echo "<p><a href='login.php'>Ir para a página de login</a></p>";
echo "<p><strong>IMPORTANTE:</strong> Remova este arquivo do servidor após o uso!</p>";
?> 
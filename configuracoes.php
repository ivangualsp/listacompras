<?php
require_once 'config.php';
verificarLogin();

$mensagem = '';
$erro = '';

// Buscar o usuário atual para exibir suas informações
$usuario_id = $_SESSION['usuario_id'];
$usuario = supabaseRequest("users?select=*&id=eq.$usuario_id", 'GET');

// Processar formulário de atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'perfil') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($nome) || empty($email)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        // Verificar se o email já existe para outro usuário
        $email_check = supabaseRequest("users?select=id&email=eq.$email&id=neq.$usuario_id", 'GET');
        
        if ($email_check['statusCode'] === 200 && !empty($email_check['body'])) {
            $erro = 'Este e-mail já está sendo usado por outro usuário.';
        } else {
            // Upload de nova imagem de perfil (em um sistema real, isso enviaria para o Supabase Storage)
            $file_path = $_SESSION['usuario_imagem']; // Valor padrão: manter a imagem atual
            
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
                // Diretório para uploads
                $upload_dir = 'uploads/perfil/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Gerar nome único para o arquivo
                $file_name = uniqid() . '_' . $_FILES['imagem']['name'];
                $file_path = $upload_dir . $file_name;
                
                // Mover o arquivo
                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $file_path)) {
                    // Arquivo enviado com sucesso
                } else {
                    $erro = 'Erro ao fazer upload da imagem.';
                }
            }
            
            if (empty($erro)) {
                // Atualizar o perfil
                $dados_atualizacao = [
                    'name' => $nome,
                    'email' => $email,
                    'profile_image' => $file_path
                ];
                
                $result = supabaseRequest("users?id=eq.$usuario_id", 'PATCH', $dados_atualizacao);
                
                if ($result['statusCode'] === 200) {
                    $mensagem = 'Perfil atualizado com sucesso!';
                    
                    // Atualizar dados da sessão
                    $_SESSION['usuario_nome'] = $nome;
                    $_SESSION['usuario_email'] = $email;
                    $_SESSION['usuario_imagem'] = $file_path;
                    
                    // Recarregar informações do usuário
                    $usuario = supabaseRequest("users?select=*&id=eq.$usuario_id", 'GET');
                } else {
                    $erro = 'Erro ao atualizar o perfil. Por favor, tente novamente.';
                }
            }
        }
    }
}

// Processar formulário de alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'senha') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $erro = 'Por favor, preencha todos os campos de senha.';
    } elseif ($nova_senha !== $confirmar_senha) {
        $erro = 'A nova senha e a confirmação não coincidem.';
    } elseif (strlen($nova_senha) < 6) {
        $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
    } else {
        // Verificar a senha atual
        if (isset($usuario['body'][0]['password']) && verificarSenha($senha_atual, $usuario['body'][0]['password'])) {
            // Senha correta, atualizar
            $senha_hash = hashSenha($nova_senha);
            
            $result = supabaseRequest("users?id=eq.$usuario_id", 'PATCH', ['password' => $senha_hash]);
            
            if ($result['statusCode'] === 200) {
                $mensagem = 'Senha alterada com sucesso!';
            } else {
                $erro = 'Erro ao alterar a senha. Por favor, tente novamente.';
            }
        } else {
            $erro = 'Senha atual incorreta.';
        }
    }
}

// Processar configurações de exibição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'exibicao') {
    $tempo_exibicao = filter_input(INPUT_POST, 'tempo_exibicao', FILTER_VALIDATE_INT);
    $ordem_exibicao = filter_input(INPUT_POST, 'ordem_exibicao', FILTER_SANITIZE_STRING);
    
    // Em um sistema real, essas configurações seriam salvas no banco de dados
    $mensagem = 'Configurações de exibição atualizadas com sucesso!';
}

// Obter configurações atuais (em um sistema real, viriam do banco de dados)
$configuracoes = [
    'tempo_exibicao' => 30,
    'ordem_exibicao' => 'sequential'
];
?>

<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Configurações</h1>
    <p class="text-gray-600 mt-1">Gerencie as configurações da sua conta e do sistema</p>
</div>

<?php if (!empty($mensagem)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p><?php echo $mensagem; ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p><?php echo $erro; ?></p>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <!-- Menu Lateral -->
    <div class="md:col-span-1">
        <div class="bg-white rounded-xl shadow-sm p-4 sticky top-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Menu</h2>
            <ul class="space-y-2">
                <li>
                    <a href="#perfil" class="flex items-center px-3 py-2 text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-user-circle mr-2"></i>
                        <span>Perfil</span>
                    </a>
                </li>
                <li>
                    <a href="#senha" class="flex items-center px-3 py-2 text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-lock mr-2"></i>
                        <span>Segurança</span>
                    </a>
                </li>
                <li>
                    <a href="#exibicao" class="flex items-center px-3 py-2 text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-tv mr-2"></i>
                        <span>Exibição</span>
                    </a>
                </li>
                <li>
                    <a href="#notificacoes" class="flex items-center px-3 py-2 text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-bell mr-2"></i>
                        <span>Notificações</span>
                    </a>
                </li>
                <li>
                    <a href="#sistema" class="flex items-center px-3 py-2 text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-cog mr-2"></i>
                        <span>Sistema</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="md:col-span-3 space-y-6">
        <!-- Perfil -->
        <section id="perfil" class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Perfil do Usuário</h2>
            
            <form action="configuracoes.php#perfil" method="post" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="acao" value="perfil">
                
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="md:w-1/3 flex flex-col items-center">
                        <div class="mb-4">
                            <img src="<?php echo htmlspecialchars($usuario['body'][0]['profile_image'] ?? 'https://randomuser.me/api/portraits/men/32.jpg'); ?>" alt="Perfil" class="w-32 h-32 rounded-full object-cover">
                        </div>
                        <label for="imagem" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 cursor-pointer text-sm flex items-center">
                            <i class="fas fa-camera mr-2"></i> Alterar Imagem
                            <input type="file" id="imagem" name="imagem" class="hidden" accept="image/*">
                        </label>
                        <p class="text-xs text-gray-500 mt-2">JPG ou PNG. Máx 2MB.</p>
                    </div>
                    
                    <div class="md:w-2/3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo <span class="text-red-500">*</span></label>
                                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['body'][0]['name'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['body'][0]['email'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Salvar Alterações</button>
                        </div>
                    </div>
                </div>
            </form>
        </section>
        
        <!-- Segurança -->
        <section id="senha" class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Alterar Senha</h2>
            
            <form action="configuracoes.php#senha" method="post" class="space-y-6">
                <input type="hidden" name="acao" value="senha">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="senha_atual" class="block text-sm font-medium text-gray-700 mb-1">Senha Atual <span class="text-red-500">*</span></label>
                        <input type="password" id="senha_atual" name="senha_atual" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div class="md:col-span-2 border-t border-gray-200 pt-4">
                        <h3 class="font-medium text-gray-700 mb-3">Nova Senha</h3>
                    </div>
                    
                    <div>
                        <label for="nova_senha" class="block text-sm font-medium text-gray-700 mb-1">Nova Senha <span class="text-red-500">*</span></label>
                        <input type="password" id="nova_senha" name="nova_senha" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label for="confirmar_senha" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha <span class="text-red-500">*</span></label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                </div>
                
                <div class="flex items-center mt-4 text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    <p>Sua senha deve ter pelo menos 6 caracteres e incluir letras e números.</p>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Alterar Senha</button>
                </div>
            </form>
        </section>
        
        <!-- Configurações de Exibição -->
        <section id="exibicao" class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Configurações de Exibição</h2>
            
            <form action="configuracoes.php#exibicao" method="post" class="space-y-6">
                <input type="hidden" name="acao" value="exibicao">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tempo_exibicao" class="block text-sm font-medium text-gray-700 mb-1">Tempo de Exibição Padrão</label>
                        <select id="tempo_exibicao" name="tempo_exibicao" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="10" <?php echo $configuracoes['tempo_exibicao'] == 10 ? 'selected' : ''; ?>>10 segundos</option>
                            <option value="30" <?php echo $configuracoes['tempo_exibicao'] == 30 ? 'selected' : ''; ?>>30 segundos</option>
                            <option value="60" <?php echo $configuracoes['tempo_exibicao'] == 60 ? 'selected' : ''; ?>>1 minuto</option>
                            <option value="300" <?php echo $configuracoes['tempo_exibicao'] == 300 ? 'selected' : ''; ?>>5 minutos</option>
                            <option value="0" <?php echo $configuracoes['tempo_exibicao'] == 0 ? 'selected' : ''; ?>>Contínuo</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="ordem_exibicao" class="block text-sm font-medium text-gray-700 mb-1">Ordem de Exibição Padrão</label>
                        <select id="ordem_exibicao" name="ordem_exibicao" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="random" <?php echo $configuracoes['ordem_exibicao'] == 'random' ? 'selected' : ''; ?>>Aleatória</option>
                            <option value="sequential" <?php echo $configuracoes['ordem_exibicao'] == 'sequential' ? 'selected' : ''; ?>>Sequencial</option>
                            <option value="priority" <?php echo $configuracoes['ordem_exibicao'] == 'priority' ? 'selected' : ''; ?>>Prioridade</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Salvar Configurações</button>
                </div>
            </form>
        </section>
        
        <!-- Notificações -->
        <section id="notificacoes" class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Notificações</h2>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <h3 class="font-medium text-gray-800">Alertas do Sistema</h3>
                        <p class="text-sm text-gray-600">Receba notificações sobre eventos do sistema</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <h3 class="font-medium text-gray-800">Dispositivos Offline</h3>
                        <p class="text-sm text-gray-600">Alerta quando um dispositivo ficar offline</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <h3 class="font-medium text-gray-800">Novos Conteúdos</h3>
                        <p class="text-sm text-gray-600">Alerta quando novo conteúdo for adicionado</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <h3 class="font-medium text-gray-800">Relatórios Semanais</h3>
                        <p class="text-sm text-gray-600">Receba um relatório semanal por e-mail</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-200">
                <button type="button" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Salvar Preferências</button>
            </div>
        </section>
        
        <!-- Sistema -->
        <section id="sistema" class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Configurações do Sistema</h2>
            
            <div class="space-y-6">
                <div>
                    <h3 class="font-medium text-gray-800 mb-2">Versão do Sistema</h3>
                    <p class="text-gray-600">MediaIndoor v1.0.0</p>
                </div>
                
                <div>
                    <h3 class="font-medium text-gray-800 mb-2">Armazenamento</h3>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: 45%"></div>
                    </div>
                    <p class="text-sm text-gray-600">450MB de 1GB usado (45%)</p>
                </div>
                
                <div>
                    <h3 class="font-medium text-gray-800 mb-2">Backup do Sistema</h3>
                    <p class="text-sm text-gray-600 mb-2">Último backup: 10/04/2023 14:30</p>
                    <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 inline-flex items-center">
                        <i class="fas fa-download mr-2"></i> Fazer Backup Agora
                    </button>
                </div>
                
                <div class="pt-6 border-t border-gray-200">
                    <h3 class="font-medium text-gray-800 mb-2">Ações Avançadas</h3>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 inline-flex items-center">
                            <i class="fas fa-sync mr-2"></i> Sincronizar Dispositivos
                        </button>
                        <button type="button" class="px-4 py-2 bg-purple-100 text-purple-800 rounded-lg hover:bg-purple-200 inline-flex items-center">
                            <i class="fas fa-broom mr-2"></i> Limpar Cache
                        </button>
                        <button type="button" class="px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 inline-flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Redefinir Sistema
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
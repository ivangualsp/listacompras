<?php
require_once 'config.php';
iniciarSessao();

// Verificar se usuário está logado
if (!estaLogado()) {
    header('Location: login.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';
$usuario_id = $_SESSION['usuario_id'];

// Verificar se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar e capturar os dados do formulário
    $tempo_exibicao = filter_input(INPUT_POST, 'tempo_exibicao', FILTER_VALIDATE_INT);
    $ordem_exibicao = filter_input(INPUT_POST, 'ordem_exibicao', FILTER_SANITIZE_SPECIAL_CHARS);
    $dispositivos = isset($_POST['dispositivos']) ? $_POST['dispositivos'] : [];
    
    // Validar os dados
    if ($tempo_exibicao === false) {
        $tempo_exibicao = 30; // Valor padrão se não for válido
    }
    
    if (!in_array($ordem_exibicao, ['random', 'sequential', 'priority'])) {
        $ordem_exibicao = 'sequential'; // Valor padrão
    }
    
    // Preparar dados para salvar
    $dados = [
        'user_id' => $usuario_id,
        'display_time' => $tempo_exibicao,
        'display_order' => $ordem_exibicao,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Verificar se já existe configuração para este usuário
    $result = supabaseRequest("user_settings?user_id=eq.$usuario_id", 'GET');
    
    if ($result['statusCode'] === 200 && !empty($result['body'])) {
        // Atualizar configuração existente
        $result = supabaseRequest("user_settings?user_id=eq.$usuario_id", 'PATCH', $dados);
        $config_id = $result['body'][0]['id'] ?? null;
    } else {
        // Criar nova configuração
        $dados['created_at'] = date('Y-m-d H:i:s');
        $result = supabaseRequest("user_settings", 'POST', $dados);
        $config_id = $result['headers']['Location'] ?? null;
        
        if ($config_id) {
            // Extrair ID da URL de localização
            $parts = explode('/', $config_id);
            $config_id = end($parts);
        }
    }
    
    // Se a operação for bem-sucedida
    if ($result['statusCode'] == 200 || $result['statusCode'] == 201 || $result['statusCode'] == 204) {
        // Processar as atribuições de dispositivos
        if (!empty($dispositivos) && $config_id) {
            // Primeiro, remover atribuições antigas
            supabaseRequest("device_settings?user_id=eq.$usuario_id", 'DELETE');
            
            // Adicionar novas atribuições
            foreach ($dispositivos as $device_id) {
                $device_setting = [
                    'user_id' => $usuario_id,
                    'device_id' => $device_id,
                    'settings_id' => $config_id,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                supabaseRequest("device_settings", 'POST', $device_setting);
            }
        }
        
        $mensagem = "Configurações salvas com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Erro ao salvar configurações. Tente novamente.";
        $tipo_mensagem = "error";
    }
    
    // Redirecionar para a página anterior com mensagem
    header("Location: dashboard.php?mensagem=" . urlencode($mensagem) . "&tipo=" . urlencode($tipo_mensagem));
    exit;
} else {
    // Se não for POST, redirecionar para o dashboard
    header('Location: dashboard.php');
    exit;
}
?> 
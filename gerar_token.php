<?php
require_once 'config.php';
verificarLogin();

// Iniciar log de depuração
error_log("=== INÍCIO DA GERAÇÃO DE TOKEN ===");

// Obter ID do dispositivo da URL
$id = $_GET['id'] ?? '';
error_log("ID do dispositivo: " . ($id ?: 'VAZIO'));

if (empty($id)) {
    error_log("ERRO: ID do dispositivo não fornecido");
    header('Location: dispositivos.php?erro=ID do dispositivo não fornecido');
    exit;
}

// Gerar um novo token de 32 caracteres
$token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimais
error_log("Token gerado: $token");

// Atualizar o dispositivo com o novo token
$update_data = [
    'token' => $token,
    'updated_at' => date('Y-m-d H:i:s')
];
error_log("Dados para atualização: " . json_encode($update_data));

// Fazer a requisição para atualizar o token
error_log("Enviando requisição PATCH para devices?id=eq.$id");
$result = supabaseRequest("devices?id=eq.$id", 'PATCH', $update_data);
error_log("Resposta da requisição - Código: " . $result['statusCode'] . ", Corpo: " . json_encode($result['body'] ?? ''));

// Verificar resultado
if ($result['statusCode'] === 204) {
    // Sucesso - redirecionar para a página de edição com mensagem
    error_log("SUCESSO: Token gerado e atualizado com sucesso");
    header("Location: dispositivo_editar.php?id=$id&mensagem=Token gerado com sucesso: $token&tipo=success");
} else {
    // Erro - redirecionar com mensagem de erro
    error_log("ERRO: Falha ao atualizar token. Código: " . $result['statusCode']);
    header("Location: dispositivo_editar.php?id=$id&mensagem=Erro ao gerar token&tipo=error");
}
error_log("=== FIM DA GERAÇÃO DE TOKEN ===");
exit; 
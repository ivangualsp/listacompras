<?php
require_once 'config.php';
verificarLogin();

// Para depuração - registrar todos os detalhes
error_log("Iniciando toggle de dispositivo");

// Obter ID do dispositivo da URL
$id = $_GET['id'] ?? '';

if (empty($id)) {
    error_log("ID do dispositivo não fornecido");
    header('Location: dispositivos.php?erro=ID do dispositivo não fornecido');
    exit;
}

error_log("ID do dispositivo: " . $id);

// Buscar dispositivo para verificar seu status atual
$result = supabaseRequest("devices?id=eq.$id&select=id,status", 'GET');
error_log("Resultado da busca: " . json_encode($result));

if ($result['statusCode'] !== 200 || empty($result['body'])) {
    error_log("Dispositivo não encontrado - Status code: " . $result['statusCode']);
    header('Location: dispositivos.php?erro=Dispositivo não encontrado');
    exit;
}

$dispositivo = $result['body'][0];
$status_atual = $dispositivo['status'] ?? '';
error_log("Status atual do dispositivo: " . $status_atual);

// Alternar o status (active -> inactive ou inactive/maintenance -> active)
$novo_status = $status_atual === 'active' ? 'inactive' : 'active';
error_log("Novo status a ser definido: " . $novo_status);

// Criar um identificador único para a atualização
$update_id = uniqid();

// Tentar múltiplos métodos para atualizar o dispositivo
// Método 1: PATCH padrão
$update_data = [
    'status' => $novo_status,
    'updated_at' => date('Y-m-d H:i:s')
];

error_log("[Método 1] Dados a serem atualizados: " . json_encode($update_data));
$endpoint = "devices?id=eq.$id";
$result_update = supabaseRequest($endpoint, 'PATCH', $update_data);
error_log("[Método 1] Resultado da atualização: " . json_encode($result_update));

// Se falhar, tentar Método 2: POST com um endpoint alternativo usando um procedimento armazenado ou function
if ($result_update['statusCode'] !== 204) {
    error_log("[Método 1] Falhou. Tentando método 2...");
    
    // Método 2: Usar a função rpc de toggle_device_status no Supabase
    $endpoint = "rpc/toggle_device_status";
    $data = ['device_id' => $id];
    $result_update = supabaseRequest($endpoint, 'POST', $data);
    error_log("[Método 2] Resultado: " . json_encode($result_update));
    
    // Se método 2 falhar, tentar Método 3
    if ($result_update['statusCode'] !== 200 && $result_update['statusCode'] !== 204) {
        error_log("[Método 2] Falhou. Tentando método 3...");
        
        // Método 3: Tentar com um token diferente ou cabeçalhos adicionais
        $endpoint = "devices?id=eq.$id";
        $headers = [
            'X-Update-ID: ' . $update_id,
            'Prefer: return=minimal'
        ];
        
        // Usar função personalizada para este caso específico
        $result_update = customPatchRequest($endpoint, $update_data, $headers);
        error_log("[Método 3] Resultado: " . json_encode($result_update));
    }
}

// Verificar se algum dos métodos foi bem-sucedido
$success = ($result_update['statusCode'] === 200 || $result_update['statusCode'] === 204);

if ($success) {
    // Sucesso - redirecionar para a visualização do dispositivo
    $mensagem = $novo_status === 'active' ? 'Dispositivo ativado com sucesso!' : 'Dispositivo desativado com sucesso!';
    error_log("Atualização bem-sucedida. Novo status: " . $novo_status . ". Redirecionando...");
    // Incluir update_id para evitar cache
    header("Location: dispositivo_visualizar.php?id=$id&mensagem=" . urlencode($mensagem) . "&tipo=success&update=$update_id");
} else {
    // Erro - redirecionar com mensagem de erro
    error_log("Todos os métodos de atualização falharam. Último status code: " . $result_update['statusCode']);
    header("Location: dispositivo_visualizar.php?id=$id&mensagem=Erro ao alterar status do dispositivo (Code: " . $result_update['statusCode'] . ")&tipo=error");
}
exit;

// Função personalizada para fazer uma requisição PATCH com cabeçalhos adicionais
function customPatchRequest($endpoint, $data, $additionalHeaders = []) {
    $url = SUPABASE_API_URL . $endpoint;
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json'
    ];
    
    // Mesclar cabeçalhos adicionais
    $headers = array_merge($headers, $additionalHeaders);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = null;
    
    if ($response === false) {
        $error = curl_error($ch);
        error_log("Erro na requisição customPatchRequest: " . $error . " (URL: $url)");
    }
    
    curl_close($ch);

    return [
        'statusCode' => $statusCode,
        'body' => json_decode($response, true),
        'error' => $error
    ];
} 
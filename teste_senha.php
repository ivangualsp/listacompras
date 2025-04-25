<?php
require_once 'config.php';

echo "<h2>Teste de Hash e Verificação de Senha</h2>";

// Testar a função de hash
$senha_original = 'admin123';
$hash_senha = hashSenha($senha_original);

echo "<p>Senha Original: $senha_original</p>";
echo "<p>Hash Gerado: $hash_senha</p>";

// Testar a função de verificação
$verificacao = verificarSenha($senha_original, $hash_senha);
echo "<p>Verificação da Senha: " . ($verificacao ? "SUCESSO" : "FALHA") . "</p>";

// Testar com a senha incorreta
$senha_incorreta = 'senha_errada';
$verificacao_incorreta = verificarSenha($senha_incorreta, $hash_senha);
echo "<p>Verificação com Senha Incorreta: " . ($verificacao_incorreta ? "SUCESSO (problema!)" : "FALHA (correto)") . "</p>";

// Consertar o problema se a função de verificação não estiver funcionando
echo "<h3>Solução de Contorno se a Verificação Falhar</h3>";
echo "<p>Se a verificação de senha estiver falhando, você pode usar o script redefinir_senha.php para redefinir a senha do administrador.</p>";

echo "<p><a href='login.php'>Voltar para o Login</a></p>";
?> 
<?php
/**
 * Funções de Análise Qualitativa para o bloco Maristtela.
 * Este ficheiro contém a lógica para comunicar com a API da OpenAI e classificar as respostas.
 *
 * @package    block_maristtela
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classifica a resposta da IA com base na sua acurácia.
 *
 * @param string $user_message A mensagem original do utilizador.
 * @param string $ai_response A resposta fornecida pela IA.
 * @return array Um array contendo a classificação de acurácia.
 */
function classify_ai_response($user_message, $ai_response) {
    // Componente corrigido para 'block_maristtela'.
    $apiKey = get_config('block_maristtela', 'apikey');
    if (empty($apiKey)) {
        return ['acuracia' => 'Chave API não configurada'];
    }

    $endpoint = 'https://api.openai.com/v1/chat/completions';

    // --- PROMPT ATUALIZADO ---
    // O prompt agora pede uma análise de "acurácia", um termo mais profissional,
    // e espera um JSON simples como resposta.
    $prompt = "Analise a seguinte interação de um chatbot educacional. A resposta da IA é factualmente acurada ou contém imprecisões?
    
    ### Pergunta do Utilizador:
    \"{$user_message}\"

    ### Resposta da IA:
    \"{$ai_response}\"

    ### Classificação:
    Responda APENAS com um objeto JSON com a chave 'acuracia' e um dos seguintes valores: 'Precisa' ou 'Imprecisa'.";

    $messages = [['role' => 'system', 'content' => $prompt]];

    $postFields = json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
        'max_tokens' => 20,
        'temperature' => 0.1,
        'response_format' => ['type' => 'json_object']
    ]);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $apiKey"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    $response = curl_exec($ch);
    $content = '';

    if (!curl_errno($ch)) {
        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? '{}';
    }
    curl_close($ch);

    $result = json_decode($content, true);

    // Valor padrão para garantir que a chave 'acuracia' sempre exista.
    $default_classification = [
        'acuracia' => 'Não classificada'
    ];

    // Mescla o resultado da API com os valores padrão para evitar erros.
    if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
        return array_merge($default_classification, $result);
    }
    
    return $default_classification;
}

/**
 * Retorna um ícone com base na categoria e no valor da classificação.
 *
 * @param string $category A categoria da classificação (apenas 'acuracia').
 * @param string $value O valor da classificação ('Acurada' ou 'Inacurada').
 * @return string O emoji correspondente.
 */
function get_classification_icon($category, $value) {
    if ($category === 'acuracia') {
        return ($value === 'Acurada') ? '✅' : '❌';
    }
    return '❓';
}

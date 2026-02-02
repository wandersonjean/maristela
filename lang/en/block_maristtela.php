<?php
// Nome do plugin
$string['pluginname'] = 'Maristela Plugin - UnB';
// Nome e labels usados no bloco e interface
$string['openai_chat'] = 'Maristtela';
$string['openai_chat_logs'] = 'Maristela Plugin - Painel de Interações com a Maristela';
$string['openai_chat:addinstance'] = 'Adicionar um novo bloco Maristtela';
$string['openai_chat:myaddinstance'] = 'Adicionar um novo bloco Maristtela no Moodle';
$string['openai_chat:viewreport'] = 'Ver o relatório de conversas';

// Informações sobre privacidade e coleta de dados
$string['privacy:metadata:openai_chat_log'] = 'Mensagens enviadas para o OpenAI registradas. Inclui o ID do usuário, a mensagem enviada, a resposta da IA, e o horário em que a mensagem foi enviada.';
$string['privacy:metadata:openai_chat_log:userid'] = 'ID do usuário que enviou a mensagem.';
$string['privacy:metadata:openai_chat_log:usermessage'] = 'Conteúdo da mensagem enviada.';
$string['privacy:metadata:openai_chat_log:airesponse'] = 'Resposta da IA.';
$string['privacy:metadata:openai_chat_log:timecreated'] = 'Horário em que a mensagem foi enviada.';
$string['privacy:chatmessagespath'] = 'Mensagens de chat enviadas para a IA';
$string['downloadfilename'] = 'historico_maristtela';

// Configurações gerais do bloco
$string['blocktitle'] = 'Maristela Plugin';

// Restrições de uso e chaves API
$string['restrictusage'] = 'Restringir uso a usuários logados';
$string['restrictusagedesc'] = 'Se marcado, apenas usuários logados poderão usar o chat.';
$string['apikey'] = 'API Key';
$string['apikeydesc'] = 'A chave API da sua conta OpenAI ou do Azure';
$string['type'] = 'Tipo de API';
$string['typedesc'] = 'O tipo de API que o plugin deve usar';
$string['logging'] = 'Ativar registro';
$string['loggingdesc'] = 'Se ativado, todas as mensagens e respostas da IA serão registradas.';

// Configurações específicas para a API do Assistente
$string['assistantheading'] = 'Configurações da API do Plugin Maristela ';
$string['assistantheadingdesc'] = 'Essas configurações são específicas para a API do do Plugin Maristela.';
$string['assistant'] = 'Assistente';
$string['assistantdesc'] = 'Escolha o Modelo';
$string['noassistants'] = 'Você ainda não criou nenhum assistente. Crie um <a target="_blank" href="https://platform.openai.com/assistants">na sua conta OpenAI</a> antes de selecioná-lo aqui.';
$string['persistconvo'] = 'Manter conversas';
$string['persistconvodesc'] = '[Teste de Funcionalidade]Se marcado, o assistente vai lembrar da conversa entre as visitas à página. No entanto, diferentes blocos manterão conversas separadas. Por exemplo, a conversa será mantida dentro do mesmo curso, mas mudar de curso iniciará uma nova conversa.';
// Testes com Azure ...
// Configurações específicas para a API do Azure
$string['azureheading'] = 'Configurações da API do Azure';
$string['azureheadingdesc'] = 'Essas configurações são específicas para a API do Azure.';
$string['resourcename'] = 'Nome do recurso';
$string['resourcenamedesc'] = 'Nome do seu recurso OpenAI no Azure.';
$string['deploymentid'] = 'ID de implantação';
$string['deploymentiddesc'] = 'Nome escolhido quando você implantou o modelo.';
$string['apiversion'] = 'Versão da API';
$string['apiversiondesc'] = 'Versão da API a ser usada nesta operação. O formato é AAAA-MM-DD.';

// Configurações para a API de Chat e API do Azure
$string['chatheading'] = 'Configurações da API de Chat';
$string['chatheadingdesc'] = 'Essas configurações se aplicam às APIs de Chat e Azure.';
$string['prompt'] = 'Prompt de início';
$string['promptdesc'] = 'O prompt que a IA receberá antes da transcrição da conversa';
$string['assistantname'] = 'Nome do assistente';
$string['assistantnamedesc'] = 'Nome que a IA usará para si mesma. Também aparece nos títulos da janela de chat.';
$string['username'] = 'Nome do usuário';
$string['usernamedesc'] = 'Nome que a IA usará para o usuário. Também aparece nos títulos da janela de chat.';
$string['showlabels'] = 'Mostrar rótulos';
$string['advanced'] = 'Avançado';
$string['advanceddesc'] = 'Parâmetros avançados enviados ao OpenAI. Mexa aqui só se souber o que está fazendo!';
$string['model'] = 'Modelo';
$string['modeldesc'] = 'Modelo utilizado:';
$string['maxlength'] = 'Comprimento máximo';
$string['maxlengthdesc'] = 'Número máximo de tokens gerados. As solicitações podem usar até 2.048 ou 4.000 tokens, compartilhados entre o prompt e a resposta. O limite exato varia por modelo. (Um token é mais ou menos 4 caracteres de texto normal em inglês)';


// Configurações específicas para cada bloco
$string['config_assistant'] = "Assistente";
$string['config_assistant_help'] = "Escolha o assistente que você quer usar para este bloco. Mais assistentes podem ser criados na conta OpenAI configurada para este bloco.";
$string['config_instructions'] = "Instruções personalizadas";
$string['config_instructions_help'] = "Substitua as instruções padrão do assistente aqui.";
$string['config_prompt'] = "Prompt de início";
$string['config_prompt_help'] = "Oi, sou a Maristela, sua assistente virtual de APC na UnB, pronta para descomplicar programação e ajudar nos desafios de código! 🚀💻";
$string['config_username'] = "Nome do usuário";
$string['config_username_help'] = "Este é o nome que a IA usará para o usuário. Se deixar em branco, será usado o nome configurado no site. Também aparece nos títulos da janela de chat.";
$string['config_assistantname'] = "Nome do assistente";
$string['config_assistantname_help'] = "Este é o nome que a IA usará para o assistente. Se deixar em branco, será usado o nome configurado no site. Também aparece nos títulos da janela de chat.";
$string['config_persistconvo'] = 'Manter conversa';
$string['config_persistconvo_help'] = 'Se esta caixa estiver marcada, o assistente vai lembrar das conversas neste bloco entre as visitas à página.';
$string['config_apikey'] = "Chave API";
$string['config_apikey_help'] = "Você pode especificar uma chave API para usar com este bloco aqui. Se deixar em branco, será usada a chave configurada no site. Se estiver usando a API de Assistentes, a lista de assistentes disponíveis será carregada com base nesta chave. Lembre-se de revisar estas configurações depois de alterar a chave API para escolher o assistente desejado.";
$string['config_model'] = "Modelo";
$string['config_model_help'] = "Escolha o modelo que vai gerar as respostas.";
$string['config_maxlength'] = "Comprimento máximo";
$string['config_maxlength_help'] = "O número máximo de tokens gerados. As solicitações podem usar até 2.048 ou 4.000 tokens, compartilhados entre o prompt e a resposta. O limite exato varia por modelo. (Um token é mais ou menos 4 caracteres de texto normal em inglês)";

// Prompts e mensagens padrão
$string['defaultprompt'] = "Oi, sou a Maristela, sua assistente virtual de APC na UnB, pronta para descomplicar programação e ajudar nos desafios de código! 🚀💻";
$string['defaultassistantname'] = 'Assistente';
$string['defaultusername'] = 'Estudante';
$string['askaquestion'] = 'Faça uma pergunta...';
$string['apikeymissing'] = 'Por favor, adicione sua chave API OpenAI nas configurações do bloco.';
$string['erroroccurred'] = 'Ocorreu um erro! Tente novamente mais tarde.';
$string['new_chat'] = 'Novo chat';
$string['popout'] = 'Maximizar a janela de chat';
$string['loggingenabled'] = "O registro está ativado.";
$string['noanalysis'] = 'Não foi possível gerar análise';
$string['errorcommunicating'] = 'Erro ao comunicar com o serviço de análise';
$string['analysis'] = 'Análise';
$string['userid'] = 'User ID';
// CORREÇÃO: A chave 'username' já estava sendo usada para outra coisa. Renomeei para 'report_username' para evitar conflitos.
$string['report_username'] = 'User Name';
$string['usermessage'] = 'User Message';
$string['airesponse'] = 'Maristela Response';
$string['context'] = 'Page';
$string['timecreated'] = 'Time';
$string['usernotfound'] = 'User not found';
$string['contextnotfound'] = 'Context not available';
$string['downloadcsv'] = 'Download as CSV';
$string['downloadexcel'] = 'Download as Excel';

// ADIÇÃO: Strings que estavam faltando e causando o erro fatal no template.
$string['chatinputregion'] = 'Chat input region';
$string['inputyourquestion'] = 'Input your question';
$string['submit'] = 'Submit';
$string['submitquestion'] = 'Submit question';
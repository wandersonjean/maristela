var questionString = 'Faça uma pergunta...';
var errorString = 'Ocorreu um erro! Por favor, tente novamente mais tarde.';
var chatData = {}; // Definir chatData num escopo mais amplo

/**
 * Cria e anexa botões de sugestão ao bloco.
 * @param {string[]} questions Array de perguntas de sugestão.
 * @param {int} blockId O ID da instância do bloco.
 * @param {string} api_type O tipo de API a ser usado.
 */
const createSuggestionButtons = (questions, blockId, api_type) => {
    let suggestionsContainer = document.querySelector('#maristtela_suggestions');
    if (!suggestionsContainer) {
        console.error('Container de sugestões não encontrado!');
        return;
    }

    suggestionsContainer.innerHTML = ''; // Limpa sugestões antigas para evitar duplicação

    questions.forEach(q => {
        const btn = document.createElement('button');
        btn.className = 'maristtela_suggestion_btn';
        btn.textContent = q;
        btn.type = 'button'; // Prevenir submissão de formulário
        btn.onclick = () => {
            const input = document.querySelector('#maristtela_input');
            input.value = q;
            addToChatLog('user', q);
            createCompletion(q, blockId, api_type);
            input.value = '';
        };
        suggestionsContainer.appendChild(btn);
    });
};

export const init = (data) => {

    const blockId = data['blockId'];
    const api_type = data['api_type'];
    const persistConvo = data['persistConvo'];
    const suggestedQuestions = data['suggestedQuestions'] || [];

    if (api_type === 'assistant') {
        chatData = localStorage.getItem("block_maristtela_data");
        if (chatData) {
            chatData = JSON.parse(chatData);
            if (chatData[blockId] && chatData[blockId]['threadId'] && persistConvo === "1") {
                fetch(`${M.cfg.wwwroot}/blocks/maristtela/api/thread.php?thread_id=${chatData[blockId]['threadId']}`)
                .then(response => response.json())
                .then(data => {
                    if (data && Array.isArray(data)) {
                        for (let message of data) {
                            addToChatLog(message.role === 'user' ? 'user' : 'bot', message.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar histórico da thread:', error);
                    chatData[blockId] = {};
                    localStorage.setItem("block_maristtela_data", JSON.stringify(chatData));
                });
            } else {
                chatData[blockId] = {};
            }
        } else {
            chatData = {[blockId]: {}};
        }
        localStorage.setItem("block_maristtela_data", JSON.stringify(chatData));
    }

    window.addEventListener('resize', event => {
        event.stopImmediatePropagation();
    }, true);

    const maristtelaInput = document.querySelector('#maristtela_input');
    if(maristtelaInput) {
        maristtelaInput.addEventListener('keyup', e => {
            if (e.key === 'Enter' && !e.shiftKey && e.target.value.trim() !== "") {
                e.preventDefault();
                addToChatLog('user', e.target.value);
                createCompletion(e.target.value, blockId, api_type);
                e.target.value = '';
            }
        });
    }

    const goButton = document.querySelector('.block_maristtela #go');
    if(goButton) {
        goButton.addEventListener('click', e => {
            const input = document.querySelector('#maristtela_input');
            if (input.value.trim() !== "") {
                addToChatLog('user', input.value);
                createCompletion(input.value, blockId, api_type);
                input.value = '';
            }
        });
    }

    const refreshButton = document.querySelector('.block_maristtela #refresh');
    if(refreshButton) {
        refreshButton.addEventListener('click', e => {
            clearHistory(blockId);
        });
    }

    const popoutButton = document.querySelector('.block_maristtela #popout');
    if(popoutButton) {
        popoutButton.addEventListener('click', e => {
            if (document.querySelector('.drawer.drawer-right')) {
                document.querySelector('.drawer.drawer-right').style.zIndex = '1041';
            }
            document.querySelector('.block_maristtela').classList.toggle('expanded');
        });
    }


    if (suggestedQuestions.length > 0) {
        createSuggestionButtons(suggestedQuestions, blockId, api_type);
    }

    require(['core/str'], function(str) {
        str.get_strings([
            {key: 'askaquestion', component: 'block_maristtela'},
            {key: 'erroroccurred', component: 'block_maristtela'},
        ]).then((results) => {
            questionString = results[0];
            errorString = results[1];
        });
    });
}

const addToChatLog = (type, message) => {
    let messageContainer = document.querySelector('#openai_chat_log');
    if (!messageContainer) return;

    const messageElem = document.createElement('div');
    messageElem.classList.add('openai_message');
    type.split(' ').forEach(className => messageElem.classList.add(className));

    const messageText = document.createElement('span');
    messageText.innerHTML = message;
    messageElem.append(messageText);
    messageContainer.append(messageElem);

    messageContainer.scrollTop = messageContainer.scrollHeight;
    const blockBody = messageContainer.closest('.block_maristtela > .card-body');
    if (blockBody) {
        blockBody.scrollTop = blockBody.scrollHeight;
    }
}

const clearHistory = (blockId) => {
    let localChatData = localStorage.getItem("block_maristtela_data");
    if (localChatData) {
        localChatData = JSON.parse(localChatData);
        if (localChatData[blockId]) {
            localChatData[blockId] = {};
            localStorage.setItem("block_maristtela_data", JSON.stringify(localChatData));
        }
    }
    const chatLog = document.querySelector('#openai_chat_log');
    if(chatLog) {
        chatLog.innerHTML = "";
    }
}

const createCompletion = (message, blockId, api_type) => {
    let threadId = null;
    let localChatData = localStorage.getItem("block_maristtela_data");
    if (localChatData) {
        chatData = JSON.parse(localChatData);
    }

    if (api_type === 'assistant' && chatData && chatData[blockId]) {
        threadId = chatData[blockId]['threadId'] || null;
    }

    const history = buildTranscript();
    const controlBar = document.querySelector('.block_maristtela #control_bar');
    const input = document.querySelector('#maristtela_input');

    controlBar.classList.add('disabled');
    input.classList.remove('error');
    input.placeholder = questionString;
    input.blur();
    addToChatLog('bot loading', '...');

    fetch(`${M.cfg.wwwroot}/blocks/maristtela/api/completion.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            message: message,
            history: history,
            blockId: blockId,
            threadId: threadId
        })
    })
    .then(response => {
        let messageContainer = document.querySelector('#openai_chat_log');
        if (messageContainer.lastElementChild && messageContainer.lastElementChild.classList.contains('loading')) {
            messageContainer.removeChild(messageContainer.lastElementChild);
        }
        controlBar.classList.remove('disabled');

        if (!response.ok) {
            // Tenta obter uma mensagem de erro do corpo da resposta, caso contrário usa o status.
            return response.json().then(err => { throw new Error(err.error.message || response.statusText); });
        }
        return response.json();
    })
    .then(data => {
        if(data.error) {
             addToChatLog('bot error', data.error.message);
             return;
        }
        addToChatLog('bot', data.message);
        if (data.thread_id) {
            if (!chatData[blockId]) chatData[blockId] = {};
            chatData[blockId]['threadId'] = data.thread_id;
            localStorage.setItem("block_maristtela_data", JSON.stringify(chatData));
        }
        input.focus();
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        addToChatLog('bot error', error.message || errorString);
        input.classList.add('error');
        input.placeholder = error.message || errorString;
    });
}

/**
 * Constrói a transcrição da conversa, excluindo a mensagem mais recente para evitar duplicação.
 * @returns {Array} Um array que representa o histórico da conversa.
 */
const buildTranscript = () => {
    const transcript = [];
    const messages = document.querySelectorAll('.openai_message');

    // Itera sobre todas as mensagens, exceto a última (que é a pergunta atual do utilizador).
    messages.forEach((message, index) => {
        if (index === messages.length - 1 || message.classList.contains('loading')) {
            return;
        }

        const role = message.classList.contains('user') ? 'user' : 'assistant';
        const content = message.querySelector('span').innerText;
        transcript.push({"role": role, "content": content});
    });

    return transcript;
}
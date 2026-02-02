export const init = () => {
    document.querySelector('#id_s_block_maristtela_type').addEventListener('change', e => {
        // Se o Tipo de API for alterado, programe a ação de salvar para que a página recarregue automaticamente com as novas opções
        document.querySelector('.settingsform').classList.add('block_maristtela');
        document.querySelector('.settingsform').classList.add('disabled');
        document.querySelector('.settingsform button[type="submit"]').click();
    });
}

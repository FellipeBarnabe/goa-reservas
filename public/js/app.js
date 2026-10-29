// Menu do celular: abre e fecha a navegação
const botaoMenu = document.querySelector('.menu-botao');
const menu = document.getElementById('menu');

if (botaoMenu && menu) {
  botaoMenu.addEventListener('click', () => {
    const aberto = menu.classList.toggle('menu--aberto');
    botaoMenu.setAttribute('aria-expanded', String(aberto));
  });
}

// Menu do celular: abre e fecha a navegação
const botaoMenu = document.querySelector(".menu-botao");
const menu = document.getElementById("menu");

if (botaoMenu && menu) {
  botaoMenu.addEventListener("click", () => {
    const aberto = menu.classList.toggle("menu--aberto");
    botaoMenu.setAttribute("aria-expanded", String(aberto));
  });
}

// Datas: o check-out não pode ser antes do dia seguinte ao check-in
const campoCheckin = document.querySelector('input[name="checkin"]');
const campoCheckout = document.querySelector('input[name="checkout"]');

if (campoCheckin && campoCheckout) {
  // "2026-11-10" -> "2026-11-11"
  const diaSeguinte = (valor) => {
    const d = new Date(valor + "T00:00:00");
    d.setDate(d.getDate() + 1);
    const mes = String(d.getMonth() + 1).padStart(2, "0");
    const dia = String(d.getDate()).padStart(2, "0");
    return `${d.getFullYear()}-${mes}-${dia}`;
  };

  campoCheckin.addEventListener("change", () => {
    if (!campoCheckin.value) return;
    const minimo = diaSeguinte(campoCheckin.value);
    campoCheckout.min = minimo;
    if (campoCheckout.value && campoCheckout.value < minimo) {
      campoCheckout.value = minimo;
    }
  });
}

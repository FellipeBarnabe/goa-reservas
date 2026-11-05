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

  const ajustarCheckout = () => {
    if (!campoCheckin.value) return;
    const minimo = diaSeguinte(campoCheckin.value);
    campoCheckout.min = minimo;
    if (campoCheckout.value && campoCheckout.value < minimo) {
      campoCheckout.value = minimo;
    }
  };

  campoCheckin.addEventListener("change", ajustarCheckout);
  ajustarCheckout(); // páginas que já abrem com a data preenchida
}

// Máscara de telefone brasileiro: (83) 99999-9999
const campoTelefone = document.querySelector('input[name="telefone"]');

if (campoTelefone) {
  const formatar = (valor) => {
    const d = valor.replace(/\D/g, "").slice(0, 11);
    if (d.length === 0) return "";
    if (d.length <= 2) return "(" + d;
    if (d.length <= 6) return "(" + d.slice(0, 2) + ") " + d.slice(2);
    if (d.length <= 10)
      return "(" + d.slice(0, 2) + ") " + d.slice(2, 6) + "-" + d.slice(6);
    return "(" + d.slice(0, 2) + ") " + d.slice(2, 7) + "-" + d.slice(7);
  };

  campoTelefone.addEventListener("input", () => {
    campoTelefone.value = formatar(campoTelefone.value);
  });
  campoTelefone.value = formatar(campoTelefone.value);
}

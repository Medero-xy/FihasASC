var btnSignin = document.querySelector("#signin");
var btnSignup = document.querySelector("#signup");

var body = document.querySelector("body");


btnSignin.addEventListener("click", function  () {
    body.className = "sign-in-js";
});

btnSignup.addEventListener("click", function () {
    body.className = "sign-up-js";
});

 // ============================
 // MODAL REDEFINIÇÃO DE SENHA
 // ============================
 
 var forgotLink = document.querySelector("#forgot-password-link");
 var resetModal = document.querySelector("#reset-password-modal");
 var resetCloseBtn = document.querySelector("#reset-close-btn");
 
 if (forgotLink && resetModal && resetCloseBtn) {
     // Abrir modal ao clicar em "Esqueceu a senha?"
     forgotLink.addEventListener("click", function (e) {
         e.preventDefault();
         resetModal.style.display = "block";
     });
 
     // Fechar modal ao clicar no X
     resetCloseBtn.addEventListener("click", function () {
         resetModal.style.display = "none";
     });
 
     // Fechar modal ao clicar fora do conteúdo
     window.addEventListener("click", function (event) {
         if (event.target === resetModal) {
             resetModal.style.display = "none";
         }
     });
 }
// toast.js
document.addEventListener("DOMContentLoaded", function () {
    console.log('Toast.js loaded');

    var toasts = document.querySelectorAll('.toast');
    toasts.forEach(function (toast) {
        toast.classList.add('show');
        setTimeout(function () {
            toast.classList.remove('show');
        }, 5000);  // Cache le toast après 5 secondes
    });
});

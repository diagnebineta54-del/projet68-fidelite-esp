// Menu latéral mobile
document.addEventListener('DOMContentLoaded', function () {
    var burger = document.getElementById('burgerBtn');
    var sidebar = document.getElementById('appSidebar');
    if (burger && sidebar) {
        burger.addEventListener('click', function () {
            sidebar.classList.toggle('ouvert');
        });
    }

    // Confirmation avant toute action de suppression
    document.querySelectorAll('.js-confirmer-suppression').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm('Confirmez-vous cette suppression ? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });

    // Validation JS générique des formulaires marqués .js-valider
    document.querySelectorAll('form.js-valider').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var valide = true;
            form.querySelectorAll('[required]').forEach(function (champ) {
                var conteneur = champ.closest('.champ') || champ.parentElement;
                var ancienMsg = conteneur.querySelector('.erreur-champ-js');
                if (ancienMsg) ancienMsg.remove();

                if (!champ.value || (champ.type === 'checkbox' && !champ.checked && champ.dataset.requiredCheck)) {
                    valide = false;
                    var msg = document.createElement('div');
                    msg.className = 'erreur-champ erreur-champ-js';
                    msg.textContent = 'Ce champ est obligatoire.';
                    conteneur.appendChild(msg);
                    champ.style.borderColor = '#A6423A';
                } else {
                    champ.style.borderColor = '';
                }

                if (champ.type === 'number' && champ.value !== '' && (champ.min !== '' && Number(champ.value) < Number(champ.min))) {
                    valide = false;
                    var msg2 = document.createElement('div');
                    msg2.className = 'erreur-champ erreur-champ-js';
                    msg2.textContent = 'Valeur minimale : ' + champ.min;
                    conteneur.appendChild(msg2);
                }
            });
            if (!valide) e.preventDefault();
        });
    });
});

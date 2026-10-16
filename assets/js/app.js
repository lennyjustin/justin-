(() => {
    const toggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.main-nav');
    if (toggle && navigation) {
        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            navigation.classList.toggle('nav-open', !expanded);
        });
    }

    document.querySelectorAll('form[data-validate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.reportValidity()) {
                event.preventDefault();
            }
        });
    });

    const results = document.querySelector('#estimate-results');
    if (results) {
        results.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
})();

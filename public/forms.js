const forms = document.querySelectorAll('form.needs-validation');
const localStorage = window.localStorage;
let savedData = [];
serializeArray = (form) => {
    return Array.from(new FormData(form)
        .entries())
        .reduce(function (response, current) {
            response[current[0]] = current[1];
            return response
        }, {})
};
forms.forEach((form) => {
    form?.querySelectorAll('[data-fr-toggle]')?.forEach((toggle) => {
        toggle.addEventListener('change', (event) => {
            let target = form.querySelector('#' + toggle.getAttribute('data-fr-toggle'));
            "true" === event.target.value ? target.classList.remove('fr-hidden') : target.classList.add('fr-hidden');
        })
    })
    form?.querySelectorAll('input[type="file"]')?.forEach((file) => {
        file.addEventListener('change', (event) => {
            if (event.target.files.length > 0) {
                if (event.target.parentElement.classList.contains('fr-fi-instagram-line'))
                    ['fr-fi-instagram-line', 'fr-py-7v'].map(v => event.target.parentElement.classList.toggle(v))
                let src = URL.createObjectURL(event.target.files[0]);
                let preview = event.target.parentElement.querySelector('img')
                preview.src = src;
                preview.classList.remove('fr-hidden')
            }
        })
    })
    form?.querySelectorAll('[data-fr-adresse-autocomplete]').forEach((autocomplete) => {
        autocomplete.addEventListener('keyup', () => {
            if (autocomplete.value.length > 3)
                fetch('https://api-adresse.data.gouv.fr/search/?q=' + autocomplete.value).then((res) => {
                    res.json().then((r) => {
                        let container = form.querySelector('#signalement-adresse-suggestion')
                        container.innerHTML = '';
                        for (let feature of r.features) {
                            let suggestion = document.createElement('div');
                            suggestion.classList.add('fr-col-12','fr-p-3v', 'fr-text-label--blue-france', 'fr-adresse-suggestion');
                            suggestion.innerHTML = feature.properties.label;
                            suggestion.addEventListener('click',()=>{
                                form.querySelector('#signalement-adresse-occupant').value = feature.properties.name;
                                form.querySelector('#signalement-cp-occupant').value = feature.properties.postcode;
                                form.querySelector('#signalement-ville-occupant').value = feature.properties.city;
                                container.innerHTML = '';
                            })
                            container.appendChild(suggestion)
                        }
                    })
                })
        })
    })
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.checkValidity() || form.id === "signalement-step-1" && null === form.querySelector('[type="radio"]:checked')) {
            event.stopPropagation();
            if (form.id === "signalement-step-1") {
                form.querySelector('[role="alert"]').classList.remove('fr-hidden')
            } else {
                form.querySelectorAll('input,textarea,select').forEach((field) => {
                    if (!field.checkValidity()) {
                        let parent = field.parentElement;
                        if (field.type === 'radio')
                            parent = field.parentElement.parentElement.parentElement;
                        [field.classList, parent.classList].forEach((f) => {
                            f.add(f[0] + '--error');
                        })
                        parent?.querySelector('.fr-error-text').classList.remove('fr-hidden');
                    }
                })
            }
        } else {
            form.querySelectorAll('input,textarea,select').forEach((field) => {
                let parent = field.parentElement;
                if (field.type === 'radio')
                    parent = field.parentElement.parentElement.parentElement;
                [field.classList, parent.classList].forEach((f) => {
                    f.remove(f[0] + '--error');
                })
                parent.querySelector('.fr-error-text')?.classList.add('fr-hidden');
            })
            if (form.name !== 'signalement')
                form.submit();
            else {
                let currentTabBtn = document.querySelector('.fr-tabs__list>li>button[aria-selected="true"]'),
                    nextTabBtn = currentTabBtn.parentElement?.nextElementSibling?.querySelector('button');
                nextTabBtn.disabled = false;
                nextTabBtn.click();
                savedData.push(serializeArray(form));
                console.log(savedData)
            }
        }
    })
})
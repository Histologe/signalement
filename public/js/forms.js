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
    form?.querySelectorAll('.toggle-criticite input[type="radio"]')?.forEach((criticite) => {
        //TODO: Forcer choix criticite sur chaque situation
        criticite.addEventListener('change', (event) => {
            event.currentTarget.parentElement.parentElement.parentElement.querySelector('.fr-toggle__input').checked = true;
            // parent.querySelector('[type="checkbox"]').checked = !parent.querySelector('[type="checkbox"]').checked;
        })
    })
    form?.querySelectorAll('.fr-toggle')?.forEach((t) => {
        t.addEventListener('change', (event) => {
            console.log('toggle')
            if (!event.target.checked)
                event.currentTarget.parentElement.querySelectorAll('.fr-collapse input[type="radio"]').forEach((radio) => {
                    radio.checked = false
                })
        })
    })
    form?.querySelectorAll('.fr-accordion__situation .fr-collapse')?.forEach((situation) => {
        situation.addEventListener('dsfr.conceal', (event) => {
            event.target.querySelectorAll('[type="radio"],[type="checkbox"]').forEach((ipt)=>{
                ipt.checked = false;
            })
        })
    })
    form?.querySelectorAll('[data-fr-toggle]')?.forEach((toggle) => {
        toggle.addEventListener('change', (event) => {
            let target = form.querySelector('#' + toggle.getAttribute('data-fr-toggle'));
            "true" === event.target.value ? target.classList.remove('fr-hidden') : target.classList.add('fr-hidden');
        })
    })
    form?.querySelectorAll('input[type="file"]')?.forEach((file) => {
        //TODO: Resize avant upload
        file.addEventListener('change', (event) => {
            if (event.target.files.length > 0) {
                if (event.target.parentElement.classList.contains('fr-fi-instagram-line')) {
                    ['fr-fi-instagram-line', 'fr-py-7v'].map(v => event.target.parentElement.classList.toggle(v));
                    let src = URL.createObjectURL(event.target.files[0]);
                    let preview = event.target.parentElement.querySelector('img')
                    preview.src = src;
                    preview.classList.remove('fr-hidden')
                } else if (event.target.parentElement.classList.contains('fr-fi-attachment-fill'))
                    ['fr-fi-attachment-fill', 'fr-fi-checkbox-circle-fill'].map(v => event.target.parentElement.classList.toggle(v))
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
                            suggestion.classList.add('fr-col-12', 'fr-p-3v', 'fr-text-label--blue-france', 'fr-adresse-suggestion');
                            suggestion.innerHTML = feature.properties.label;
                            suggestion.addEventListener('click', () => {
                                // console.log(feature.geometry.coordinates)
                                form.querySelector('#signalement-adresse-occupant').value = feature.properties.name;
                                form.querySelector('#signalement-cp-occupant').value = feature.properties.postcode;
                                form.querySelector('#signalement-ville-occupant').value = feature.properties.city;
                                form.querySelector('#signalement-geoloc-lat-occupant').value = feature.geometry.coordinates[0];
                                form.querySelector('#signalement-geoloc-lng-occupant').value = feature.geometry.coordinates[1];
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
    /*    console.log(form.querySelectorAll('[type="checkbox"]:checked').length)*/
        if (!form.checkValidity()
            || form.id === "signalement-step-1" && null === form.querySelector('[type="radio"]:checked')
            || form.id === "signalement-step-1" && form.querySelectorAll('[type="checkbox"]:checked').length !== form.querySelectorAll('[type="radio"]:checked').length ) {
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
                        field.addEventListener('change', () => {
                            if (field.checkValidity()) {
                                [field.classList, parent.classList].forEach((f) => {
                                    f.remove(f[0] + '--error');
                                })
                                parent.querySelector('.fr-error-text')?.classList.add('fr-hidden');
                            }
                        })
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
                if (nextTabBtn) {
                    if (nextTabBtn.hasAttribute('data-fr-last-step')) {
                        document.querySelector('#recap-signalement-situation').innerHTML = '';
                        forms.forEach((form) => {
                            form.querySelectorAll('input,textarea,select').forEach((input) => {
                                if (document.querySelector('#recap-' + input.id))
                                    document.querySelector('#recap-' + input.id).innerHTML = input.value;
                                else if (input.classList.contains('signalement-situation') && input.checked)
                                    document.querySelector('#recap-signalement-situation').innerHTML += '- ' + input.value + '<br>';
                            })
                        })
                    }
                    nextTabBtn.disabled = false;
                    nextTabBtn.click();
                }
                if (!nextTabBtn) {
                    event.submitter.disabled = true;
                    ['fr-fi-checkbox-circle-fill', 'fr-fi-refresh-fill'].map(v => event.submitter.classList.toggle(v));
                    event.submitter.innerHTML = "En cours d'envoi..."
                    let formData = new FormData();
                    forms.forEach((form) => {
                        let data = serializeArray(form);
                        for (let i = 0; i < Object.keys(data).length; i++) {
                            let x = Object.keys(data)[i];
                            let y = Object.values(data)[i];
                            formData.append(x, y);
                        }
                    })
                    fetch('envoi', {
                        method: "POST",
                        body: formData
                    }).then((r) => {
                        if (r.ok) {
                            r.json().then((res) => {
                                if (res.response === "success") {
                                    document.querySelector('main').innerHTML = "Ok"
                                } else {
                                    event.submitter.disabled = true;
                                    ['fr-fi-checkbox-circle-fill', 'fr-fi-refresh-fill'].map(v => event.submitter.classList.toggle(v));
                                    alert('Erreur signalement !')
                                }
                            })
                        } else {
                            event.submitter.disabled = true;
                            ['fr-fi-checkbox-circle-fill', 'fr-fi-refresh-fill'].map(v => event.submitter.classList.toggle(v));
                            alert('Erreur signalement !')
                        }
                    })
                }
            }
        }
    })
})
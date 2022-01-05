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
    form.querySelectorAll('input[type="file"]').forEach((file) => {
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
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.checkValidity() || form.id === "signalement-step-1" && null === form.querySelector('[type="radio"]:checked')) {
            event.stopPropagation();
            if (form.id === "signalement-step-1") {
                form.querySelector('[role="alert"]').classList.remove('fr-hidden')
            } else {
                form.querySelectorAll('input,textarea,select').forEach((field) => {
                    if (!field.checkValidity()) {
                        [field.classList, field.parentElement.classList].forEach((f) => {
                            f.add(f[0] + '--error');
                        })
                        field.parentElement.querySelector('.fr-error-text').classList.remove('fr-hidden');
                    }
                })
            }
        } else {
            form.querySelectorAll('input,textarea,select').forEach((field) => {
                [field.classList, field.parentElement.classList].forEach((f) => {
                    f.remove(f[0] + '--error');
                })
                field.parentElement.querySelector('.fr-error-text')?.classList.add('fr-hidden');
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
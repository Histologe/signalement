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
    form.addEventListener('change',(event)=>{
        if(event.target.name==="signalement[isProprioAverti]")
        {
            if(event.target.value === "true")
                form.querySelector('#methode-contact').classList.remove('fr-hidden')
            else
                form.querySelector('#methode-contact').classList.add('fr-hidden')

        }
        if(event.target.name==="signalement[isOkIntervention]")
        {
            if(event.target.value === "true")
                form.querySelector('#raison-refus').classList.remove('fr-hidden')
            else
                form.querySelector('#raison-refus').classList.add('fr-hidden')

        }

    })
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
                        let parent = field.parentElement;
                        if(field.type === 'radio')
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
                if(field.type === 'radio')
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
Node.prototype.addEventListeners = function (eventNames, eventFunction) {
    for (eventName of eventNames.split(' '))
        this.addEventListener(eventName, eventFunction);
}
document.querySelectorAll('[name="bo-filter-form"]').forEach((filterForm) => {
    filterForm.addEventListener('change', (evt) => {
        filterForm.submit();
    })
})
document.querySelectorAll('.fr-checkbox-affectation').forEach((checkbox) => {
    checkbox.addEventListener('change', (event) => {
        checkbox.disabled = true;
        fetch(checkbox.getAttribute('data-toggle-fetch')).then(r => r.json().then(res => {
            ['fr-fi-checkbox-circle-fill', 'fr-fi-close-circle-fill', 'fr-text-label--green-emeraude', 'fr-text-label--red-marianne'].map(c => checkbox.parentElement.parentElement.classList.toggle(c))
            checkbox.disabled = false;
        }))
    })
})
document.querySelectorAll('[data-file-delete]').forEach(fileDelete => {
    fileDelete.addEventListeners('click touchdown', event => {
        if (confirm('Voulez-vous vraiment supprimer cette photo ?')) {
            let formData = new FormData;
            formData.append('_token', fileDelete.getAttribute('data-token'))
            fetch(fileDelete.getAttribute('data-file-delete'), {
                method: 'POST',
                body: formData,
            }).then(r => {
                if (r.ok) {
                    fileDelete.parentElement.parentElement.remove();
                }
            })
        }
    })
})
document.querySelectorAll('.fr-input--file-signalement').forEach(inputFile => {
    inputFile.addEventListener('change', evt => {
        evt.target.form.submit();
    })
})

document?.querySelector('#partenaire_add_user')?.addEventListeners('click touchdown', (event) => {
    event.preventDefault();
    let template = document.importNode(document.querySelector('#partenaire_add_user_row').content, true)
    let container = document.querySelector('#partenaire_add_user_placeholder')
    let count = container.querySelectorAll('.partenaire-row-user')?.length
    let row = document.createElement('div');
    row.classList.add('fr-grid-row', 'fr-grid-row--gutters', 'fr-grid-row--middle', 'partenaire-row-user');
    template.querySelectorAll('label,input,select').forEach(field => {
        field.id = field?.id?.replaceAll('__ID__', count);
        field.name = field?.name?.replaceAll('__ID__', count);
        if (field.tagName === 'LABEL')
            field.setAttribute('for', field.getAttribute('for').replaceAll('__ID__', count))
    })
    row.appendChild(template);
    container.appendChild(row);
})

document?.querySelectorAll('.partenaire-user-delete')?.forEach(userDelete => {
    userDelete.addEventListeners('click touchdown', event => {
        event.preventDefault();
        if (confirm('Voulez-vous vraiment supprimer cet utilisateur ?')) {
            let formData = new FormData;
            formData.append('_token', userDelete.getAttribute('data-token'))
            fetch(userDelete.getAttribute('data-user-delete'), {
                method: 'POST',
                body: formData,
            }).then(r => {
                if (r.ok) {
                    userDelete.closest('.partenaire-row-user').remove();
                }
            })
        }
    })
})

document.querySelector('#fr-bug-report-modal').addEventListeners('dsfr.disclose dsfr.conceal', (event) => {
    let form = event.target.querySelector('form[name="bug-report"]');
    let formData = new FormData(form);
    if (event.type === 'dsfr.disclose') {
        event.target.querySelector('#bug-report-success').classList.add('fr-hidden')
        form.classList.remove('fr-hidden');
        event.target.querySelector('#bug-report-submit').disabled = false;
        html2canvas(document.body).then(function (canvas) {
            canvas.toBlob(blob => {
                formData.append('capture', blob, 'capture.png');
            });
        });
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();
            event.target.querySelector('#bug-report-submit').disabled = true;
            /*    formData.append('bug-report[content]',form.querySelector('#bug-report-content').value)
                formData.append('bug-report[url]',form.querySelector('#bug-report-url').value)
                formData.append('bug-report[route]',form.querySelector('#bug-report-route').value)
                formData.append('_token',form.querySelector('#bug-report-token').value)*/
            fetch(form.action, {
                method: 'POST',
                body: formData
            }).then(r => r.text().then(res => {
                console.log(res)
                form.classList.add('fr-hidden');
                event.target.querySelector('#bug-report-success').classList.remove('fr-hidden')
            }))
        })
    } else {
        formData = null;
        event.target.querySelector('textarea').value = '';
    }
})
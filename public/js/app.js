const sortTableFunction = (table) => {
    return function (ev) {
        if (ev.target.tagName.toLowerCase() == 'a') {
            sortRows(table, siblingIndex(ev.target.parentNode));
            ev.preventDefault();
        }
    };
}
const siblingIndex = (node) => {
    let count = 0;

    while (node = node.previousElementSibling) {
        count++;
    }

    return count;
}
const sortRows = (table, columnIndex) => {
    let rows = table.querySelectorAll("tbody tr"),
        sel = "thead th:nth-child(" + (columnIndex + 1) + ")",
        sel2 = "td:nth-child(" + (columnIndex + 1) + ")",
        classList = table.querySelector(sel).classList,
        values = [],
        cls = "",
        allNum = true,
        val,
        index,
        node;

    if (classList) {
        if (classList.contains("date")) {
            cls = "date";
        } else if (classList.contains("number")) {
            cls = "number";
        }
    }

    for (index = 0; index < rows.length; index++) {
        node = rows[index].querySelector(sel2);
        val = node.innerText;

        if (isNaN(val)) {
            allNum = false;
        } else {
            val = parseFloat(val);
        }

        values.push({value: val, row: rows[index]});
    }

    if (cls == "" && allNum) {
        cls = "number";
    }

    if (cls == "number") {
        values.sort(sortNumberVal);
        values = values.reverse();
    } else if (cls == "date") {
        values.sort(sortDateVal);
    } else {
        values.sort(sortTextVal);
    }

    for (let idx = 0; idx < values.length; idx++) {
        table.querySelector("tbody").appendChild(values[idx].row);
    }
}
const sortNumberVal = (a, b) => {
    return sortNumber(a.value, b.value);
}
const sortNumber = (a, b) => {
    return a - b;
}
const sortDateVal = (a, b) => {
    let dateA = Date.parse(a.value),
        dateB = Date.parse(b.value);

    return sortNumber(dateA, dateB);
}
const sortTextVal = (a, b) => {
    let textA = (a.value + "").toUpperCase();
    let textB = (b.value + "").toUpperCase();

    if (textA < textB) {
        return -1;
    }

    if (textA > textB) {
        return 1;
    }

    return 0;
}
let invalid, tables = document.querySelectorAll("table.sortable"),
    table,
    thead,
    headers,
    i,
    j;
for (i = 0; i < tables.length; i++) {
    table = tables[i];

    if (thead = table.querySelector("thead")) {
        headers = thead.querySelectorAll("th");

        for (j = 0; j < headers.length; j++) {
            headers[j].innerHTML = "<a href='#'>" + headers[j].innerText + "</a>";
        }

        thead.addEventListener("click", sortTableFunction(table));
    }
}
document?.querySelectorAll(".fr-pagination__link:not([aria-current])").forEach((e => {
    let t, r, a, n = document.querySelector(".fr-pagination__link--prev"),
        i = document.querySelector(".fr-pagination__link--next"),
        u = document.querySelector(".fr-pagination__link--first"),
        l = document.querySelector(".fr-pagination__link--last"), o = 1, c = parseInt(l.getAttribute("data-page"));
    e.addEventListener("click", (e => {
        let p = new FormData;
        p.append("pagination", "true"), p.append("search", document.querySelector("#header-search-input").value), p.append("status", document.querySelector("#filter_statut").value), p.append("ville", document.querySelector("#filter_ville").value);
        let d = document?.querySelector(".fr-pagination__link[aria-current]"), g = e.target;
        g !== n && g !== i && g !== u && g !== l ? o = parseInt(g.getAttribute("data-page")) : g === i ? o = parseInt(d.getAttribute("data-page")) + 1 : g === n ? o = parseInt(d.getAttribute("data-page")) - 1 : g === l ? o = parseInt(c) : g === u && (o = parseInt(1)), p.append("page", o), t = document.querySelector('.fr-pagination__link[data-page="' + o + '"]'), fetch("#", {
            method: "POST",
            body: p
        }).then((e => e.text().then((e => {
            let p = document.querySelector("#signalements-result");
            p.innerHTML = e, p.querySelectorAll("tr").forEach((e => {
                gauge = new Gauge(e.querySelector(".gauge-signalement")).setOptions(opts), gauge.set(e.getAttribute("data-score"))
            })), d.ariaCurrent = null, d.href = "#", t.removeAttribute("href"), t.ariaCurrent = "page", 1 !== o && o !== c ? r = [u, n, i, l] : 1 === o ? (r = [i, l], a = [u, n]) : o === c && (r = [u, n], a = [i, l]), r.forEach((e => {
                e.removeAttribute("aria-disabled"), e.href = "#"
            })), a && a.forEach((e => {
                e.removeAttribute("href"), e.ariaDisabled = "true"
            }))
        }))))
    }))
}));
document?.querySelectorAll('[name="bo-filter-form"]').forEach((filterForm) => {
    filterForm.addEventListener('change', () => {
        filterForm.submit();
    })
})
document?.querySelectorAll('[data-fr-select-target]')?.forEach(t => {
    let source = document?.querySelector('#' + t.getAttribute('data-fr-select-source'));
    let target = document?.querySelector('#' + t.getAttribute('data-fr-select-target'));
    t.addEventListeners('click touchdown', () => {
        let selected = [...source.selectedOptions]
        selected.map(s => {
            let groupPartenaire = s.parentElement.getAttribute('data-select-group-id'), group;
            /*target.append(s);*/
            if (!target.querySelector('[data-select-group-id="' + groupPartenaire + '"]')) {
                group = document.createElement('optgroup');
                group.setAttribute('data-select-group-id', groupPartenaire);
                group.label = s.parentElement.label;
            } else {
                group = target.querySelector('[data-select-group-id="' + groupPartenaire + '"]')
            }
            group.append(s)
            target.append(group)
        })
    })
})
document?.querySelector('#signalement-affectation-form-submit')?.addEventListeners('click touchdown', (e) => {
    e.preventDefault();
    e.target.disabled = true;
    e.target?.form?.querySelectorAll('option').forEach(o => {
        o.selected = true;
    })
    document?.querySelectorAll('#signalement-affectation-form-row,#signalement-affectation-loader-row').forEach(el => {
        el.classList.toggle('fr-hidden')
    })
    //POST
    let formData = new FormData(e.target.form);
    fetch(e.target.getAttribute('formaction'), {
        method: 'POST',
        body: formData
    }).then(r => {
        if (r.ok) {
            /*r.json().then(res => {*/
            window.location.reload(true)
            /*})*/
        }
    })
    console.log(e.target.form);
})
document?.querySelectorAll('.fr-input--file-signalement').forEach(inputFile => {
    inputFile.addEventListener('change', evt => {
        evt.target.form.submit();
    })
})
document?.querySelector('#partenaire_add_user,#situation_add_critere')?.addEventListeners('click touchdown', (event) => {
    event.preventDefault();
    let template, container, count, row, className;
    if (event.target.id === 'partenaire_add_user') {
        template = document.importNode(document.querySelector('#partenaire_add_user_row').content, true)
        container = document.querySelector('#partenaire_add_user_placeholder')
        className = 'partenaire-row-user'
        count = container.querySelectorAll('.' + className)?.length
    } else {
        template = document.importNode(document.querySelector('#situation_add_critere_row').content, true)
        container = document.querySelector('#situation_add_critere_placeholder')
        className = 'situation-row-critere';
        count = container.querySelectorAll('.' + className)?.length
    }
    row = document.createElement('div');
    row.classList.add('fr-grid-row', 'fr-grid-row--gutters', 'fr-grid-row--middle', 'fr-background-alt--blue-france', 'fr-mb-5v', className);
    template.querySelectorAll('label,input,select,button,textarea').forEach(field => {
        field.id = field?.id?.replaceAll('__ID__', count);
        field.name = field?.name?.replaceAll('__ID__', count);
        if (field.tagName === 'LABEL')
            field.setAttribute('for', field.getAttribute('for').replaceAll('__ID__', count))
        if (field.tagName === 'BUTTON')
            field.addEventListeners('click touchdown', (event) => {
                event.target.closest('.' + className).remove();
            })
    })
    row.appendChild(template);
    container.appendChild(row);
})
document?.querySelectorAll('[data-delete]')?.forEach(deleteBtn => {
    deleteBtn.addEventListeners('click touchdown', event => {
        event.preventDefault();
        let className;
        if (event.target.classList.contains('partenaire-user-delete'))
            className = '.partenaire-row-user';
        else if (event.target.classList.contains('situation-critere-delete'))
            className = '.situation-row-critere';
        else if (event.target.classList.contains('signalement-file-delete'))
            className = '.signalement-file-item';
        else if (event.target.classList.contains('signalement-row-delete'))
            className = '.signalement-row';
        else if (event.target.classList.contains('partenaire-row-delete'))
            className = '.partenaire-row';
        if (confirm('Voulez-vous vraiment supprimer cet élément ?')) {
            let formData = new FormData;
            formData.append('_token', deleteBtn.getAttribute('data-token'))
            fetch(deleteBtn.getAttribute('data-delete'), {
                method: 'POST',
                body: formData,
            }).then(r => {
                if (r.ok) {
                    deleteBtn.closest(className).remove();
                }
            })
        }
    })
})
document?.querySelector('#fr-bug-report-modal').addEventListeners('dsfr.disclose dsfr.conceal', (event) => {
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
document?.querySelectorAll('.fr-password-toggle')?.forEach(pwdToggle => {
    pwdToggle.addEventListeners('click touchdown', (event) => {
        ['fr-fi-eye-off-fill', 'fr-fi-eye-fill'].map(c => {
            event.target.classList.toggle(c);
        })
        let pwd = event.target.parentElement.querySelector('[name^="password"]');
        "text" !== pwd.type ? pwd.type = "text" : pwd.type = "password";
    })
})
document?.querySelector('form[name="login-creation-mdp-form"]')?.querySelectorAll('[name^="password"]').forEach(pwd => {
    pwd.addEventListener('input', () => {
        let pass = document?.querySelector('form[name="login-creation-mdp-form"] #login-password').value;
        let repeat = document?.querySelector('form[name="login-creation-mdp-form"] #login-password-repeat').value;
        let pwdMatchError = document?.querySelector('form[name="login-creation-mdp-form"] #password-match-error');
        let submitBtn = document?.querySelector('form[name="login-creation-mdp-form"] #submitter');
        if (pass !== repeat) {
            document?.querySelector('form[name="login-creation-mdp-form"]').querySelectorAll('.fr-input-group').forEach(iptGroup => {
                iptGroup.classList.add('fr-input-group--error')
                iptGroup.querySelector('.fr-input').classList.add('fr-input--error')
            })
            submitBtn.disabled = true;
            pwdMatchError.classList.remove('fr-hidden')
        } else {
            document?.querySelector('form[name="login-creation-mdp-form"]').querySelectorAll('.fr-input-group--error,.fr-input--error').forEach(iptError => {
                ['fr-input-group--error', 'fr-input--error'].map(c => {
                    iptError.classList.remove(c)
                });
            })
            pwdMatchError.classList.add('fr-hidden');
            submitBtn.disabled = false;
        }
    })
})


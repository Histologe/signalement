document.querySelectorAll('[name="bo-filter-form"]').forEach((filterForm)=>{
    filterForm.addEventListener('change',(evt)=>{
        filterForm.submit();
    })
})
document.querySelectorAll('.fr-checkbox-affectation').forEach((checkbox)=>{
    checkbox.addEventListener('change',(event)=>{
        checkbox.disabled = true;
        fetch(checkbox.getAttribute('data-toggle-fetch')).then(r=>r.json().then(res=>{
            ['fr-fi-checkbox-circle-fill','fr-fi-close-circle-fill','fr-text-label--green-emeraude','fr-text-label--red-marianne'].map(c=>checkbox.parentElement.parentElement.classList.toggle(c))
            checkbox.disabled = false;
        }))
    })
})
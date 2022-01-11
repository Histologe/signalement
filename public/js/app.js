document.querySelectorAll('[name="bo-filter-form"]').forEach((filterForm)=>{
    filterForm.addEventListener('change',(evt)=>{
        filterForm.submit();
    })
})
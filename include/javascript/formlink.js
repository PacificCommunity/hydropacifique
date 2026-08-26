/*  
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Fonctions pour transmettre des données à une page par proctocole FORM
*/

function linkSubmitForm(url, tabFields) 
{
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.target = '_blank';

    tabFields.forEach(function(field) 
    {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = field.name;
        input.value = field.value;        
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
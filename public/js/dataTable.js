$(document).ready(function(){
    $('#table-backoffice').DataTable({
        language: {
            url: '/js/dataTables.french.json'
        
        },
        "aoColumnDefs": [
            {'bSortable': false, 'aTargets': [1,2,5,6]}
        ]
    });
});


$(document).ready(function(){
    $('#table-comments').DataTable({
        language: {
            url: '/js/dataTables.french.json'
        
        },
        "aoColumnDefs": [
            {'bSortable': false, 'aTargets': [4]}
        ]
    });
});

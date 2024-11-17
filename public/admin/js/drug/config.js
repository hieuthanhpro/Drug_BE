function uploadImg(id) {
   $('#drug_id').val(id);
}
$(document).ready(function() {

    $('#tableChildRow').DataTable({
        processing: true,
        serverSide: true,
        ajax: urlTable,
        columns: [
            { data: 'image', name: 'image' },
            { data: 'name', name: 'name' },
        { data: 'drug_code', name: 'drug_code' },
        { data: 'company', name: 'company' },
        { data: 'registry_number', name: 'registry_number' },
            { data: 'action', name: 'action' }
    ]
});

} );
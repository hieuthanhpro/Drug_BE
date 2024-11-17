$('#tableChildRow').DataTable({
    serverSide: true,
    processing: true,
    ajax: urlTable,
    columns: [
    {   data: 'image',
        name: 'image',
        render:function (data, type, full, meta) {
        var img ='';
            if (data != null){
                img = "<img src=\"" + data + "\" height=\"50\"/>";
            }
            return img;
        }
        
    },
    {data: 'name', name: 'name'},
    {data: 'drug_code', name: 'drug_code'},
    {data: 'substances', name: 'substances'},
    {data: 'company', name: 'company'},
    {data: 'registry_number', name: 'registry_number'},
    {data: 'action', name: 'action', orderable: false, searchable: false},
]
});
function uploadImg(id) {
    if (id != ''){
        document.getElementById('drug_id').value=id;
    }
}
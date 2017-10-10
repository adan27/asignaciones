$(document).ready(function(){
    $('.btn-delete').click(function(e){
        e.preventDefault(); //para evitar la recarga de la pagina cuando se de clic en el boton 
        
        var row = $(this).parents('tr');
        var id = row.data('id');
        var form = $('#form-delete');
        var url = form.attr('action').replace(':USER_ID', id);
        var data = form.serialize();
        
        bootbox.confirm(message, function(res){
            if(res == true){
                $('#delete-progress').removeClass('hidden');
                $.post(url, data, function(result){
                    $('#delete-progress').addClass('hidden');
                    if(result.removed == 1){
                        row.fadeOut();
                        $('#message').removeClass('hidden');
                        $('#user-message').text(result.message);
                        var totalUser = $('#total').text();
                        
                        if($.isNumeric(totalUser)){
                            $('#total').text(totalUser-1);
                        }else{
                            $('#total').text(result.countUser);
                        }
                    }else{
                        $('#message-danger').removeClass('hidden');
                        $('#user-message-danger').text(result.message);
                    }
                }).fail(function(){
                    alert('ERROR');
                    row.show();
                });
            }
        });
    });   
});
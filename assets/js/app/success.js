/**
 * Created by William on 8/6/2016.
 */


!function(){

    $(document).ready(function(){
        loadBook();

        $("#eventWrapper").on('click', '#continueBrowsingButton', function(e){
            //Todo: implement the cancel process
            alert("Todo: redirect to referrer page?");
            return false;
            //==================//
        });
        $("#eventWrapper").on('click', '#contactAdminButton', function(e){
            //Todo: redirect to contact admin page
            alert("Todo: redirect to contact page or compose notification popup?");
            return false;
            //==================//
        });

        $("#eventWrapper").on('click', '#cancelRequestButton', function(e){
            e.preventDefault();
            var bookId = $(this).data('book-id');
            $.ajax({
                method: "POST",
                url: "model/cancel.php",
                data: {bookId:bookId}
            }).done(function( data ) {
                if(!data.error){
                    window.location.href = "book.php?id="+bookId;
                }else {
                    if(data.error_code == 403){
                        console.log(data);
                        window.location.href = "login.php";
                    }else{
                        alert(data.error_message);
                    }
                }
            });
        });
    });

    function loadBook() {
        var bookId = $('#bookId').val();
        $.ajax({
            method: "GET",
            url: "api/books/"+bookId
        }).done(function( data ) {
            var template = $.templates("#bookTemplate");
            var htmlOutput = template.render(data);
            $("#bookContainer").html(htmlOutput);
        });
    }
}();
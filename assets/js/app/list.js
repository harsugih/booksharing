/**
 * Created by William on 8/6/2016.
 */


!function(){

    $(document).ready(function(){

        loadBooks();

        // $("#eventWrapper").on('click', '#borrowButton', function(e){
        //     e.preventDefault();
        //     var bookId = $(this).data('book-id');
        //     $.ajax({
        //         method: "POST",
        //         url: "model/borrow.php",
        //         data: {bookId:bookId}
        //     }).done(function( data ) {
        //         if(!data.error){
        //             window.location.href = "success.php?id="+bookId;
        //         }else {
        //             if(data.error_code == 403){
        //                 console.log(data);
        //                 window.location.href = "login.php";
        //             }else{
        //                 alert(data.error_message);
        //             }
        //         }
        //     });
        // });
    });

    function loadBooks() {
        var categoryId = $('#categoryId').val();
        $.ajax({
            method: "GET",
            url: "api/books?categoryId="+categoryId
        }).done(function( data ) {
            var template = $.templates("#bookListTemplate");

            var htmlOutput = template.render(data);
            $("#bookListContainer").html(htmlOutput);
            var categoryName = "";
            if(!jQuery.isEmptyObject(data["data"][0]["categories"][0]["name"])){
                categoryName = data["data"][0]["categories"][0]["name"];
                $("#breadcrumbCategoryName").text(categoryName);
            }
            var categoryId = "";
            if(!jQuery.isEmptyObject(data["data"][0]["categories"][0]["category_id"])){
                categoryId = data["data"][0]["categories"][0]["category_id"];
                $("#breadcrumbCategoryName").attr("href", "/booksharing/list.php?categoryId="+categoryId);
            }

        });
    }
}();
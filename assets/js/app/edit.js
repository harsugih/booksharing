/**
 * Created by William on 8/6/2016.
 */


!function(){

    $(document).ready(function(){
        loadBook();

        $("#eventWrapper").on('click', '#saveButton', function(e){
            e.preventDefault();
            var bookId = $(this).data('book-id');
            var data = $("#editForm").serialize();
            $.ajax({
                method: "POST",
                url: "model/saveBook.php",
                data: data
            }).done(function( data ) {
                if(!data.error){
                    alertify.success("Book updated");
                    window.location.href = "edit.php?id="+bookId;
                }else {
                    if(data.error_code == 403){
                        console.log(data);
                        window.location.href = "login.php";
                    }else{
                        alertify.error(data.error_message);
                    }
                }
            });
        });

        $(document).on('change', ':file', function() {
            var input = $(this),
                numFiles = input.get(0).files ? input.get(0).files.length : 1,
                label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
            input.trigger('fileselect', [numFiles, label]);
        });

        $(document).on('fileselect', ':file', function(event, numFiles, label) {
            console.log(numFiles);
            console.log(label);
            $("#pictureCover").val(label);
            alertify.confirm("Edit Book","Change book's cover?",
                function(){
                    // alertify.success('Ok');
                    $("#upload-cover-form").submit();
                },
                function(){
                    alertify.error('Cancel');
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
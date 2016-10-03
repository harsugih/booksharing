/**
 * Created by William on 8/6/2016.
 */


!function(){

    $(document).ready(function(){

        loadBook();

        $("#eventWrapper").on('click', '#saveButton', function(e){
            e.preventDefault();
            var bookId = $(this).data('book-id');
            if(!validate()){
                return false;
            }
            var data = $("#editForm").serialize();
            $.ajax({
                method: "POST",
                url: "model/saveBook.php",
                data: data
            }).done(function( data ) {
                if(!data.error){
                    alertify.success("Book updated");
                    window.location.href = "edit.php?id="+data.book_id;
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
        $("#eventWrapper").on('click', '#privateUseButton', function(e){
            e.preventDefault();
            var bookId = $(this).data('book-id');
            var status = $(this).data('status');

            $.ajax({
                method: "POST",
                url: "model/setPrivateUse.php",
                data: { bookId : bookId,
                        status : status}
            }).done(function( data ) {
                if(!data.error){
                    if(status=='PRIVATE'){
                        alertify.success("Book status changed to private");
                    }else if(status=='AVAILABLE'){
                        alertify.success("Book status changed to available");
                    }
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

        $("#eventWrapper").on('click', '#deleteButton', function(e){
            e.preventDefault();
            var bookId = $(this).data('book-id');
            alertify.confirm("Edit Book","Permanently delete the book?",
                function(){
                    $.ajax({
                        method: "POST",
                        url: "model/delete.php",
                        data: { bookId : bookId}
                    }).done(function( data ) {
                        if(!data.error){
                            alertify.success("Book deleted");
                            window.location.href = "list.php";
                        }else {
                            if(data.error_code == 403){
                                console.log(data);
                                window.location.href = "login.php";
                            }else{
                                alertify.error(data.error_message);
                            }
                        }
                    });
                },
                function(){
                    alertify.error('Cancel');
                });

        });

        $("#eventWrapper").on('click', '#edit-input-group-label', function(e){

            var bookId = $(this).data('book-id');
            if(bookId==0){
                e.preventDefault();
                alertify.alert("New book","Please save the new book first before uploading the cover");
                return false;
            }
        });

        $(document).on('change', ':file', function() {
            var bookId = $(this).data('book-id');
            if(bookId==0){
                alertify.message("Please save the new book first before uploading the cover");
                return false;
            }
            var input = $(this),
                numFiles = input.get(0).files ? input.get(0).files.length : 1,
                label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
            input.trigger('fileselect', [numFiles, label]);
        });

        $(document).on('fileselect', ':file', function(event, numFiles, label) {

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
        if(bookId==0){
            var data = getBookSkeleton();
            var template = $.templates("#bookTemplate");
            var htmlOutput = template.render(data);
            $("#bookContainer").html(htmlOutput);
            $('#authorSelect').multiselect();
            $("#deleteButton").hide();
            return true ;
        }


        $.ajax({
            method: "GET",
            url: "api/books/"+bookId
        }).done(function( data ) {
            var template = $.templates("#bookTemplate");

            var htmlOutput = template.render(data);
            $("#bookContainer").html(htmlOutput);
            var categoryName = "";
            try {
                if(!jQuery.isEmptyObject(data["data"][0]["categories"][0]["name"])){
                    categoryName = data["data"][0]["categories"][0]["name"];
                    $("#breadcrumbCategoryName").text(categoryName);
                }
                var categoryId = "";
                if(!jQuery.isEmptyObject(data["data"][0]["categories"][0]["category_id"])){
                    categoryId = data["data"][0]["categories"][0]["category_id"];
                    $("#breadcrumbCategoryName").attr("href", "/booksharing/list.php?categoryId="+categoryId);
                }
            }
            catch(err) {
                alertify.error(err.message);
            }

            $('#authorSelect').multiselect();
            var selectedAuthor = new Array();
            $(".author-span").each(function(){
                var authorId = $(this).data("author-id");
                console.log(authorId);
                selectedAuthor.push(authorId);
            });
            if(selectedAuthor.length>0){
                $("#authorSelect").val(selectedAuthor);
                $("#authorSelect").multiselect("refresh");
            }

            // $('#editAuthorSpan').hide();
        });
    }

    function validate(){
        var result = true;
        if($("#titleInput").val().trim()==""){
            alertify.error("Title can not be empty");
            $("#titleInput").css('background-color', '#f0ad4e');
            result= false;
        }else{
            $("#titleInput").css('background-color', 'white');
        }

        var isbn = $("#isbnInput").val();
        if(isbn!=""){
            result = isValidISBN(isbn);
            if(!result){
                $("#isbnInput").css('background-color', '#f0ad4e');
                alertify.error("ISBN invalid");
            }else {
                $("#isbnInput").css('background-color', 'white');
            }
        }

        var loanPeriod = $('#loanPeriodInput').val();
        if(!(Math.floor(loanPeriod) == loanPeriod && $.isNumeric(loanPeriod))){
            result = false;
            $("#loanPeriodInput").css('background-color', '#f0ad4e');
            alertify.error("Loan period invalid");
        }else{
            $("#loanPeriodInput").css('background-color', 'white');
        }

        return result;
    }

    function isValidISBN (isbn) {
        isbn = isbn.replace(/[^\dX]/gi, '');
        if(isbn.length != 10){
            return false;
        }
        var chars = isbn.split('');
        if(chars[9].toUpperCase() == 'X'){
            chars[9] = 10;
        }
        var sum = 0;
        for (var i = 0; i < chars.length; i++) {
            sum += ((10-i) * parseInt(chars[i]));
        };
        return ((sum % 11) == 0);
    }

    function getBookSkeleton(){
        var bookSkeleton = {
            "data": [
                {
                    "book_id": "0",
                    "title": "",
                    "description": "",
                    "category_id": "1",
                    "isbn": "",
                    "publisher_id": null,
                    "language": "DE",
                    "user_id": "1",
                    "status": "NEW",
                    "loan_period": "7",
                    "enter_date": "",
                    "recommended": "0",
                    "rating": null,
                    "image": "0.jpg",
                    "authors": [

                    ],
                    "user": {
                        "user_id": "",
                        "first_name": "",
                        "last_name": "",
                        "email": "",
                        "status": "",
                        "total_borrowed": "0"
                    },
                    "categories": [
                        {
                            "category_id": "1",
                            "name": "New Book",
                            "total": "0"
                        }
                    ]
                }
            ],
            "page": 1
        };
        return bookSkeleton;
    }
}();
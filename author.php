<?php
include_once(__DIR__.'/model/class/UserSession.php');

$lock = true;
include_once (__DIR__.'/lock.php');

if(!isset($_SESSION["user"])){
	header('Location: /index.php');
	die();
}else{
	$userSession =  unserialize($_SESSION["user"]);
	$userId = $userSession->userId;
	$isAdmin = (integer) $userSession->admin;
	if(!$isAdmin){
		header('Location: /index.php');
		die();
	}
}

include_once(__DIR__.'/header.php');
?>

    <!--=== Shop Product ===-->
    <div class="shop-product" >
        <!-- Breadcrumbs v5 -->
        <div class="container">
            <ul class="breadcrumb-v5">
                <li><a href="index.php"><i class="fa fa-home"></i></a></li>
                <li class="active">Personal Data</a></li>
            </ul>
        </div>
        <!-- End Breadcrumbs v5 -->


    </div>
    <!--=== End Shop Product ===-->

     <!--=== Illustration v2 ===-->
    <div class="container" id="eventWrapper">
        <div class="container">
			<div class="heading heading-v1 margin-bottom-20">
				<h2> Authors
				</h2>
			</div>
			<div class="margin-bottom-40">
				<button type="button" class="btn-u btn-u-sea-shop" id="addButton">+</button>
			</div>
			<div class="row" id="authorContainer">

			</div><!--/end row-->
		</div>
	</div>
<?php include_once(__DIR__.'/footer.php'); ?>
</div><!--/wrapper-->

<!-- JS Global Compulsory -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/jquery/jquery-migrate.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>

<!-- Alertify.js -->
<script src="//cdn.jsdelivr.net/alertifyjs/1.8.0/alertify.min.js"></script>

<!-- Get the data -->
<script src="assets/js/jsrender.js"></script>
<script src="assets/js/app/header.js"></script>
<script src="assets/js/app/author.js"></script>

<script id="authorTemplate" type="text/x-jsrender">
	<div class="edit-author-div">
		<table class="table table-hover">
            {{for data}}
                <tr>
                    <td>{{:name}}</td>
                    <td width="15%">
                    	<a href="#" class="edit-button" data-name="{{:name}}" data-author-id="{{:author_id}}"><i class="fa fa-pencil" title="Edit" aria-hidden="true"></i></a>
                    	&nbsp;<a href="#" class="delete-button" data-name="{{:name}}" data-author-id="{{:author_id}}"><i class="fa fa-trash-o" title="Delete" aria-hidden="true"></i></a>
					</td>
                </tr>
            {{/for}}
		</table>
	</div>
</script>

<!--[if lt IE 9]>
    <script src="assets/plugins/respond.js"></script>
    <script src="assets/plugins/html5shiv.js"></script>
    <script src="assets/js/plugins/placeholder-IE-fixes.js"></script>
<![endif]-->

</body>
</html>

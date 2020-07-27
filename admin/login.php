<?php
session_start();
include "../connection.php";
$form_username="";
$form_password="";
$msg_username="";
$admin_name="";
if(isset($_POST["btn_username"])){
	$qry=mysqli_query($con,"select admin_name from admin where admin_name='".mres($con,$_POST["username"])."'") or die(mysqli_error($con));
	if(mysqli_num_rows($qry)> 0){
	$admin_name= $_POST["username"];
	$form_username="none";
	$form_password="block";
}
else {
	$msg_username='
	<div id="login-alert" class="alert alert-danger" cols-sm-12">Utilizatorul nu este corect</div>
	';
	$form_username="block";
	$form_password="none";
}}

else if(isset($_POST["btn_password"])){
	$qry=mysqli_query($con,"select * from admin where admin_name='".mres($con,$_POST["admin_name"])."' and password='".md5(mres($con,$_POST["password"]))."'") or die(mysqli_error($con));
	if(mysqli_num_rows($qry)> 0){
	$_SESSION["username"]= $_POST["admin_name"];	
    header("Location:index.php");
}
else {
	$msg_password='
	<div id="login-alert" class="alert alert-danger" cols-sm-12">Parola nu este corecta</div>
	';
	$form_username="none";
	$form_password="block";
}}
else {
	$form_username="block";
	$form_password="none";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>OpenEdu Romania admin</title>
<meta charset="utf-8"
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/custom.css">
<script src="../js/jquery-3.4.1.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
 <div class="row">
	<div class="col-lg-4 col-md-4 col-sm-12 col-xs-4" >
	<h3>Administrator</h3>
	</div>
	<div class="col-lg-8 col-md-8 col-sm-12 col-xs-8" >
	<h3>OpenEdu Romania</h3>
	</div> </div>
<div class="row">
<nav class="navbar navbar-default">
  <div class="container">
 </div> </nav> </div>
<div class="row">
	<div class="container">    
       <div style="margin-top:50px;" class="col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">                        
	   <div class="panel panel-info" style='display:<?php echo $form_username;?>'>
                    <div class="panel-heading">
                    <div class="panel-title">Login administrator: Utilizator</div>
                    </div>     
                    <div class="panel-body" >
					<?php echo $msg_username;?>    
						<form id="form_username" class="form-horizontal" rol="form" method="post" action='<?php echo $_SERVER["PHP_SELF"];?>'>
                            <div style="margin-bottom:25px" class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                        <input type="text" class="form-control" name="username" id ="username" placeholder="username">        
                            </div>
							<div style="margin-top:10px" class="form-group">
							<div class="col-sm-12 controls">
							<input type="submit" id="btn_username" name="btn_username" class="btn btn-info btn-block btn-large" value="Mai departe">
							</div> </div>				
						</form>
							</div> </div> 
	<div class="panel panel-info" style='display:<?php echo $form_password;?>'>
                    <div class="panel-heading">
                    <div class="panel-title">Login administrator: Parola</div>
                    </div>     
                    <div class="panel-body" >
					<?php echo $msg_password;?>    
                        <form id="form_password" class="form-horizontal" rol="form" method="post" action='<?php echo $_SERVER["PHP_SELF"];?>'>
							<input type="hidden" name="admin_name" value='<?php echo $admin_name;?>'>
                            <div style="margin-bottom:25px" class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                                        <input type="password" class="form-control" name="password" id="password" placeholder="password">        
                            </div>
							<div style="margin-top:10px" class="form-group">
							<div class="col-sm-12 controls">
							<input type="submit" id="btn_password" name="btn_password" class="btn btn-info btn-block btn-large" value="Login">
							</div> </div>
						</form> 
					</div> </div> </div> </div> </div> </div>
<script>
	$(document).ready(function(){
	$("#btn_username").click(function(e){
		if ($('#username').val() == ''){
			$('#username').css("border-color","#175cbd");
			$('#username').css("background","#DC143C");
			e.preventDefault();
		}
		else{
			$('form_username').unbind('submit').submit();
		}
	});
	
	$("#btn_password").click(function(e){
		if ($('#password').val() == ''){
			$('#password').css("border-color","#175cbd");
			$('#password').css("background","#DC143C");
			e.preventDefault();
		}
		else{
			$('form_password').unbind('submit').submit();
		}
	});
	});
</script>
</body>
</html>
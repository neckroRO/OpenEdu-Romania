<?php
session_start();
if(!isset($_SESSION["username"])){
	header("Location:login.php");
}
include "../connection.php";
include "header.php";
?>
<div class="row" style="padding-left:0px; padding-right:0px;">
<div class="col-lg-3 col-md-3 col-sm-3 col-xs-12" style="padding-left:0px; padding-right:0px;">
<?php include "left_menu.php";?>
</div>
<div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
<div class="panel panel-info" style='display:<?php echo $form_username;?>'>
        <div class="panel-heading">
        <div class="panel-title">Adaugare noi materii</div>
		</div>     
                    <div class="panel-body" >
					
					        <form id="form_adaugare_materii" class="form-horizontal" rol="form" method="post" action='<?php echo $_SERVER["PHP_SELF"];?>'>
                            <div style="margin-bottom:25px" class="input-group">
                            <span class="input-group-addon">Nume materie</span>
                            <input type="text" class="form-control" name="text_nume_materie" id ="text_nume_materie" placeholder="Introduceti materia noua ">        
                            </div>
							<div style="margin-top:10px" class="form-group">
							<div class="col-sm-12 controls">
							<input type="submit" id="btn_username" name="btn_username" class="btn btn-info btn-block btn-large" value="Mai departe">
							</div>				
							</div>				
							</form>
					</div>
</div> </div> </div>			
<?php include "footer.php";?>
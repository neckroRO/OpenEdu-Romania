<?php
$con = mysqli_connect("localhost","openedu","openedu","openedu") or die("Nu se poate conecta la baza de date");
function mres($con,$data){
	return mysqli_real_escape_string($con,rtrim(ltrim($data)));
}
?>
<?php
	define(__SCRIPT_NAME__,"register");

	session_start();

	require_once('func/func_misc.php');
    require_once('includes/header.php');

	notification();

?>
<form id="loginForm" name="loginForm" method="post" action="proc/process_register.php">
<input name="date" type="hidden"  id="date" value='<?php echo("time()"); ?>' />
  <table width="300" border="0" align="center" cellpadding="2" cellspacing="0">
    <tr>
      <th>Username </th>
      <td><input name="username" type="text" class="textfield" id="username" /></td>
    </tr>
    <tr>
      <th>Work week </th>
      <td><input name="dworkweek" type="text" class="textfield" id="dworkweek" /></td>
    </tr>
    <tr>
      <th>Password</th>
      <td><input name="password" type="password" class="textfield" id="password" /></td>
    </tr>
    <tr>
      <th>Confirm Password </th>
      <td><input name="cpassword" type="password" class="textfield" id="cpassword" /></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><input type="submit" name="Submit" value="Register" /></td>
    </tr>
  </table>
</form>
<?php
      require_once('includes/footer.php');
?>
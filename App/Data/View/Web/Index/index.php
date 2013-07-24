<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>SharpPHP, Hello World!</title>

</head>
<body>
	<table class="list">
		<tr><th>用户id</th><th>用户名</th><th>密码</th></tr>
		<?php foreach ($users as $uid => $u) { ?>
		<tr><td><?php echo $uid; ?></td><td><?php echo $u['username']; ?></td><td><?php echo $u['password']; ?></td></tr>
		<?php } ?>
	</table>
	
</body>
</html>
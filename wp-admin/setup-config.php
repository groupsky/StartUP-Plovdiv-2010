<?php
define('WP_INSTALLING', true);
//These three defines are required to allow us to use require_wp_db() to load the database class while being wp-content/wp-db.php aware
define('ABSPATH', dirname(dirname(__FILE__)).'/');
define('WPINC', 'wp-includes');
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

require_once(ABSPATH . WPINC . '/compat.php');
require_once(ABSPATH . WPINC . '/functions.php');
require_once(ABSPATH . WPINC . '/classes.php');

if (!file_exists(ABSPATH . 'wp-config-sample.php'))
    wp_die('Липсва файлът wp-config-sample.php. Той е основата на конфигурацията, която ще бъде създадена. Моля, качете го отново, като може да го вземете от инсталационния аврхив на WordPress.');

$configFile = file('../wp-config-sample.php');

// Check if wp-config.php has been created
if (file_exists(ABSPATH . 'wp-config.php'))
	wp_die("<p>Файлът 'wp-config.php' вече съществува. Ако искате да промените настройките записани в него ще трябва първо да го изтриете. След това можете да <a href='install.php'>започнете инсталацията</a>.</p>");

// Check if wp-config.php exists above the root directory but is not part of another install 
if (file_exists(ABSPATH . '../wp-config.php') && ! file_exists(ABSPATH . '../wp-settings.php'))
	wp_die("<p>Файлът 'wp-config.php' вече съществува. Ако искате да промените настройките записани в него ще трябва първо да го изтриете. След това можете да <a href='install.php'>започнете инсталацията</a>.</p>");

if (isset($_GET['step']))
	$step = $_GET['step'];
else
	$step = 0;

function display_header(){
	header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>WordPress &rsaquo; Създаване на конфигурационния файл</title>
<link rel="stylesheet" href="css/install.css" type="text/css" />

</headsp>
<body>
<h1 id="logo"><img alt="WordPress" src="images/wordpress-logo.png" /></h1>
<?php
}//end function display_header();

switch($step) {
	case 0:
		display_header();
?>

<p>Добре дошли в света на WordPress. Като за начало ще е нужна малко информация за свързване с базата от данни:</p>
<ol>
	<li>Име на базата от данни</li>
	<li>Потребителско име за връзка с базата</li> 
	<li>Парола</li> 
	<li>Адрес на MySQL сървъра</li> 
	<li>Представка за имената на таблиците (ако искате да имате повече от една WordPress инсталация в една база от данни) </li>
</ol>
<p><strong>Ако по някаква причина този инструмент не успее да запише вашата конфигурация &mdash; не се безпокойте. Цялата информация се пази в прост файл, който ако се наложи може да редактирате сами. Просто отворете <code>wp-config-sample.php</code> във вашия любим текстов редактор, попълнете необходимите данни и го запазете под име <code>wp-config.php</code>. </strong></p>
<p>Най-вероятно вие вече имате всичката необходима информация от вашата хостинг компания, но ако все пак нещо липсва ще се наложи да се свържете с тях преди да продължите инсталацията на WordPress. Когато всичко е готово може да <a href="setup-config.php?step=1">продължите нататък</a>! </p>

<p class="step"><a href="setup-config.php?step=1" class="button">Хайде!</a></p>
<?php
	break;

	case 1:
		display_header();
	?>
<form method="post" action="setup-config.php?step=2">
	<p>Въведете данните за връзката с базата от данни. Ако не сте убедени за някоя от стойностите, попитайте вашата хостинг компания.</p>
	<table class="form-table"> 
		<tr> 
		  <th scope="row"><label for="dbname">Име на базата от данни</label></th> 
		  <td><input name="dbname" id="dbname" type="text" size="25" value="wordpress" /></td>
		  <td>В коя база от данни ще бъдат създадени таблиците на на тази WordPress инсталация</td> 
		</tr> 
		<tr> 
		  <th scope="row"><label for="uname">Потребителско име</label></th>
		  <td><input name="uname" id="uname" type="text" size="25" value="username" /></td>
		  <td>Потребителското име за връзка с MySQL сървъра</td> 
		</tr> 
		<tr> 
		  <th scope="row"><label for="pwd">Парола</label></th> 
		  <td><input name="pwd" id="pwd" type="text" size="25" value="password" /></td>
		  <td>...и паролата за горното потребителско име</td> 
		</tr> 
		<tr> 
		  <th scope="row"><label for="dbhost">Адрес на MySQL сървъра</label></th>
		  <td><input name="dbhost" id="dbhost" type="text" size="25" value="localhost" /></td>
		  <td>В 99% от случаите стойността трябва да си остане localhost</td> 
		</tr>
		<tr>
		  <th scope="row"><label for="prefix">Представка за таблиците</label></th>
		  <td><input name="prefix" id="prefix" type="text" id="prefix" value="wp_" size="25" /></td>
		  <td>Променете, ако искате да ползвате няколко инсталации на WordPress в една база от данни</td>
		</tr> 
	</table>
	<p class="step"><input name="submit" type="submit" value="Изпращане" class="button" /></p>
</form>
<?php
	break;

	case 2:
	$dbname  = trim($_POST['dbname']);
	$uname   = trim($_POST['uname']);
	$passwrd = trim($_POST['pwd']);
	$dbhost  = trim($_POST['dbhost']);
	$prefix  = trim($_POST['prefix']);
	if (empty($prefix)) $prefix = 'wp_';

	// Test the db connection.
	define('DB_NAME', $dbname);
	define('DB_USER', $uname);
	define('DB_PASSWORD', $passwrd);
	define('DB_HOST', $dbhost);

	// We'll fail here if the values are no good.
	require_wp_db();
	if ( !empty($wpdb->error) )
		wp_die($wpdb->error->get_error_message());

	foreach ($configFile as $line_num => $line) {
		switch (substr($line,0,16)) {
			case "define('DB_NAME'":
				$configFile[$line_num] = str_replace("wordpress", $dbname, $line);
				break;
			case "define('DB_USER'":
				$configFile[$line_num] = str_replace("'username'", "'$uname'", $line);
				break;
			case "define('DB_PASSW":
				$configFile[$line_num] = str_replace("'password'", "'$passwrd'", $line);
				break;
			case "define('DB_HOST'":
				$configFile[$line_num] = str_replace("localhost", $dbhost, $line);
				break;
			case '$table_prefix  =':
				$configFile[$line_num] = str_replace('wp_', $prefix, $line);
				break;
		}
	}
	
	if ( ! is_writable(ABSPATH) ) :
		display_header();
?>
<p>За съжаление нямаме право за писане върху файла <code>wp-config.php</code>.</p>
<p>Може да създадете файла <code>wp-config.php</code> ръчно и да сложите следния текст вътре:</p>
<textarea cols="90" rows="15"><?php 
	foreach( $configFile as $line ) {
		echo htmlentities($line);
}
?></textarea>
<p>След като сте готови, натиснете бутона &#8222;Инсталация&#8220;.</p>
<p class="step"><a href="install.php" class="button">Инсталация</a></p>
<?php 
	else:
 	$handle = fopen(ABSPATH . 'wp-config.php', 'w');
 	foreach( $configFile as $line ) {
 		fwrite($handle, $line);
 	}
 	fclose($handle);
 	chmod(ABSPATH . 'wp-config.php', 0666);
 	display_header();
?>
<p>Хм, всичко работи! Още една част от инсталацията бе успешно премината. WordPress вече може да си говори с вашата база от данни. Ако сте готови, може да продължите към&hellip;</p> 

<p class="step"><a href="install.php" class="button">Същинската инсталация</a></p>
<?php
	endif;
	break;
}
?>
</body>
</html>

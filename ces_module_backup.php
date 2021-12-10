<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

class Copy {

	function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);

			foreach ($objects as $object) {
				if ($object != '.' && $object != '..') {
					if (filetype($dir . '/' . $object) == 'dir') {$this->rrmdir($dir . '/' . $object);} else {unlink($dir . '/' . $object);}
				}
			}

			reset($objects);
			rmdir($dir);
		}
	}

	function recursive_copy($src, $dst) {
		if (is_dir($src)) {
			$dir = opendir($src);

			if (!mkdir($dst, 0777, true)) {
				$error = error_get_last();
				echo $error['message'] . '</br>';
			}

			while (($file = readdir($dir))) {
				if ((substr($file, -1) != '_') && ($file != '.') && ($file != '..')) {
					if (is_dir($src . '/' . $file)) {
						$this->recursive_copy($src . '/' . $file, $dst . '/' . $file);
					} else {
						// print_r($file);die;
						copy($src . '/' . $file, $dst . '/' . $file);
					}
				}
			}
			closedir($dir);

			return;
		} elseif (is_file($src)) {
			$dir = substr($dst, 0, strrpos($dst, '/'));
			mkdir($dir, 0777, true);

			copy($src, $dst);
		} else {
			die('<strong>Not found : </strong> ' . $src);
			// print_r($src);die;
		}
	}
}

$copy = new Copy();
$done = '';
$current_path = getcwd() . '/';

if (isset($_POST['submit']) && $_POST['submit'] == 'Submit') {
	// print_r(getcwd());
	try {
		$json = file_get_contents($_FILES['file']['tmp_name']);
	} catch (Exception $e) {
		print_r($e->getMessage());
		die;
	}

	$json = json_decode($json, true);

	$fix_dst = isset($json['default_copy_path']) ? $current_path . $json['default_copy_path'] . '/' : $current_path;

	@$copy->rrmdir($fix_dst . $folder);

	@require_once $current_path . $json['default_path'] . '/admin/config.php';
	@require_once $current_path . $json['default_path'] . '/config.php';

	foreach ($json['all_files'] as $key => $folder) {
		$copy->recursive_copy($current_path . $json['default_path'] . '/' . $folder, $fix_dst . $folder);
	}
	foreach ($json['single_file'] as $key => $file) {
		$copy->recursive_copy($current_path . $json['default_path'] . '/' . $file, $fix_dst . $file);
	}
	$done = 'success';
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>CES Module Backup</title>
</head>
<body>
	<?php if ($done == 'success') {?>
		<div style="display: block; width: 100%; background-color: lightgreen; font-size: 24px; text-align: center">Success</div>
	<?php }?>

	<form action="" method="post" enctype="multipart/form-data">
		<label>Upload Schema File</label> <input name="file" type="file" />
		<br />
		<br />
		<input type="submit" name="submit" value="Submit"/>
	</form>
</body>
</html>

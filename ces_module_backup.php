<?php
	if(empty($_GET['er'])) {
		error_reporting(0);
	} else {
		error_reporting(E_ALL);
	}
	class Copy {

		function recursive_copy($src, $dst) {
			if(is_dir($src)) {
				$dir = opendir($src);

				mkdir($dst, 0777, true);

				while(( $file = readdir($dir)) ) {
					if (( substr($file, -1) != '_' ) && ( $file != '.' ) && ( $file != '..' )) {
						if ( is_dir($src . '/' . $file) ) {
							$this->recursive_copy($src .'/'. $file, $dst .'/'. $file);
						}
						else {
							copy($src .'/'. $file, $dst .'/'. $file);
						}
					}
				}
				closedir($dir);

				return;
			} elseif(is_file($src)) {
				$dir = substr($dst, 0, strrpos($dst, '/'));
				mkdir($dir, 0777, true);
				copy($src, $dst);
			} else {
				die('<strong>Not found : </strong> ' . $src);
			}
		}
	}

	$copy = new Copy();
	$done = '';
	$current_path = getcwd() . '/';

	if(isset($_POST['submit']) && $_POST['submit'] == 'Submit') {
		// print_r(getcwd());
		try {
			$json = file_get_contents($_FILES['file']['tmp_name']);
		} catch(Exception $e) {
			print_r($e->getMessage());
			die;
		}

		$json = json_decode($json, true);


		$fix_dst = '';
		if(isset($json['default_copy_path'])) {
			$json['default_copy_path'] = str_replace("//", "/", $json['default_copy_path']);
			if(strpos($json['default_copy_path'], 'home')) {
				$fix_dst = $json['default_copy_path'] . '/';
			} else {
				$fix_dst = $current_path . $json['default_copy_path'] . '/';
			}
		} else {
			$fix_dst = $current_path;
		}

		require_once $current_path . $json['default_path'] . '/admin/config.php';
		require_once $current_path . $json['default_path'] . '/config.php';

		$old_umask = umask();
		umask(000);

		$current_new_path = str_replace("//", "/", $current_path . $json['default_path'] . '/');
		foreach ($json['all_files'] as $key => $folder) {
			$copy->recursive_copy($current_new_path . $folder, $fix_dst . $folder);
		}
		foreach ($json['single_file'] as $key => $file) {
			$copy->recursive_copy($current_new_path . $file, $fix_dst . $file);
		}

		umask($old_umask);
		$done = 'success';
	}
?>

<!DOCTYPE html>
<html>
<head>
	<title>CES Module Backup</title>
</head>
<body>
	<?php if($done == 'success') { ?>
		<div style="display: block; width: 100%; background-color: lightgreen; font-size: 24px; text-align: center">Success</div>
	<?php } ?>

	<form action="" method="post" enctype="multipart/form-data">
		<label>Upload Schema File</label> <input name="file" type="file" />
		<br />
		<br />
		<input type="submit" name="submit" value="Submit"/>
	</form>
</body>
</html>

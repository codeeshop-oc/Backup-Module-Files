<?php
	error_reporting(1);	
	class Copy {

		public $name = 'this';
		
		function recursive_copy($src, $dst) {
			if(is_dir($src)) {
				$dir = opendir($src);
				
				mkdir($dst, 0777, true);
				
				while(( $file = readdir($dir)) ) {
					if (( $file != '.' ) && ( $file != '..' )) {
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
			}
				// print_r($src);die;
		}
	}

	$copy = new Copy();

	if(isset($_POST['submit']) && $_POST['submit'] == 'Submit') {
		// print_r(getcwd());			
		try {
			$json = file_get_contents($_FILES['file']['tmp_name']);		
		} catch(Exception $e) {
			print_r($e->getMessage());
			die;
		}

		$json = json_decode($json, true);
	
		$fix_dst = (isset($json['default_copy_path']) ? $json['default_copy_path'] : getcwd()) . '/';
		
		require_once $json['default_path'] . '/admin/config.php';
		require_once $json['default_path'] . '/config.php';

		// foreach ($json['all_files'] as $key => $folder) {
		// 	$copy->recursive_copy($json['default_path'] . '/' . $folder, $fix_dst . $folder);
		// }
		foreach ($json['single_file'] as $key => $file) {
			// print_r($json['default_path'] . '/' . $file);
			$copy->recursive_copy($json['default_path'] . '/' . $file, $fix_dst . $file);
		}
	}
?>

<!DOCTYPE html>
<html>
<head>
	<title>CES Module Backup</title>
</head>
<body>
	<form action="" method="post" enctype="multipart/form-data"> 
		<label>Backup File Name</label> <input name="file_name" type="text" value="" placeholder="default name is backup1" />
		<br />
		<br />
		<label>Backup File</label> <input name="file" type="file" />
		<br />
		<br />
		<input type="submit" name="submit" value="Submit"/>
	</form>
</body>
</html>
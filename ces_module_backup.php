<?php
	if(empty($_GET['er'])) {
		error_reporting(0);
	} else {		
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}

	class BackupFiles {
		public static $json = [];
		public static $current_path = '';

		public function updateJson($json_data, $current_path) {
			self::$json = $json_data;
			self::$current_path = $current_path;
		}

		public function recursive_copy($src, $dst) {
			if(is_dir($src)) {
				$dir = opendir($src);

				@mkdir($dst, 0777, true);

				while(( $file = readdir($dir)) ) {
					if (( substr($file, -1) != '_' ) && ( $file != '.' ) && ( $file != '..' )) {
						if ( is_dir($src . '/' . $file) ) {
							$this->recursive_copy($src .'/'. $file, $dst .'/'. $file);
						}
						else {
							$this->default_copy($src .'/'. $file, $dst .'/'. $file);
						}
					}
				}
				closedir($dir);

				return;
			} elseif(is_file($src)) {
				$dir = substr($dst, 0, strrpos($dst, '/'));
				mkdir($dir, 0777, true);
				$this->default_copy($src, $dst);
			} else {
				die('<strong>Not found : </strong> ' . $src);
			}
		}

		public function default_copy($source, $destination) {
			$all_files_ignore = self::$json['all_files_ignore'];

			$new_current_file = str_replace(self::$current_path, '', $source);			
			if(!in_array($new_current_file, $all_files_ignore)) {
				copy($source, $destination);
			}
		}

		public function move_to_folder($uploadFolder, $zipFileName) {
	 		if (rename($zipFileName, $uploadFolder . '/' . $zipFileName)) {
		        echo "ZIP file moved to the 'upload' folder.";
		    } else {
		        echo "Failed to move the ZIP file to the 'upload' folder.";
		    }
			echo "<br>";
		}

		public function zipFiles($folderToZip, $zipFileName, $current_path) {
			// Create a new ZipArchive object
			$zip = new ZipArchive();

			// Open the ZIP file for writing
			if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
			    // Add files and subdirectories to the ZIP file
			    $files = new RecursiveIteratorIterator(
			        new RecursiveDirectoryIterator($folderToZip),
			        RecursiveIteratorIterator::SELF_FIRST
			    );
			    
		        foreach ($files as $file) {
			        $file = realpath($file);
			        $localName = str_replace($current_path, '', $file);
			        if (is_dir($file)) {
			            // Do not add directories
			            continue;
			        } elseif (is_file($file)) {
			            $zip->addFile($file, $localName);
			        }
			    }

			    // Close the ZIP file
			    $zip->close();

			    echo "ZIP archive created successfully!";
			} else {
			    echo "Failed to create ZIP archive.";
			}
			echo "<br>";
		}
	}

	$backupFiles = new BackupFiles();
	$done = '';
	$current_path = getcwd() . '/';

	if(isset($_POST['submit']) && $_POST['submit'] == 'Submit') {
		try {
			$json = file_get_contents($_FILES['file']['tmp_name']);
		} catch(Exception $e) {
			print_r($e->getMessage());
			die;
		}

		$json = json_decode($json, true);

		$backupFiles->updateJson($json, $current_path);

		$folder_name = $json['default_copy_path'];
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
			$backupFiles->recursive_copy($current_new_path . $folder, $fix_dst . $folder);
		}
		foreach ($json['single_file'] as $key => $file) {
			$backupFiles->recursive_copy($current_new_path . $file, $fix_dst . $file);
		}

		// echo $fix_dst;
		// die;
		echo "Zip Files<br>";
		$zipFileName = 'oc_module.zip';
		$backupFiles->zipFiles($fix_dst, $zipFileName, $current_path);
		$backupFiles->move_to_folder($fix_dst, $zipFileName);

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

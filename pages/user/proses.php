<?php
ob_start();
session_start();


require_once "../../config/database.php";
require_once "../../helpers/db_helpers.php";
require_once "../../helpers/session_helpers.php";


if (empty($_SESSION['username']) && empty($_SESSION['password'])){
	echo "<meta http-equiv='refresh' content='0; url=index.php?alert=1'>";
}

else {

	// ── Autorización ─────────────────────────────────────────────────────────
	// Antes cualquier usuario con sesión (un cajero, una empresa cliente) podía crear o editar cuentas,
	// incluida una con perfil Super Admin. Solo entra quien es Super Admin o tiene el módulo "usuarios".
	$puedeGestionarUsuarios = esSuperAdmin($mysqli);
	if (!$puedeGestionarUsuarios && !empty($_SESSION['id_user'])) {
		$uidSes = (int)$_SESSION['id_user'];
		$rMod = mysqli_query($mysqli, "SELECT 1 FROM perfil_modulo pm JOIN usuario u ON u.per_id = pm.per_id
		                               WHERE u.id_user = $uidSes AND pm.pm_modulo = 'usuarios' LIMIT 1");
		if ($rMod && mysqli_num_rows($rMod) > 0) { $puedeGestionarUsuarios = true; }
		if (!$puedeGestionarUsuarios) {
			$nomPerfilSes = mysqli_real_escape_string($mysqli, $_SESSION['permisos_acceso'] ?? '');
			$rMod2 = mysqli_query($mysqli, "SELECT 1 FROM perfil_modulo pm JOIN perfil p ON p.per_id = pm.per_id
			                                WHERE LOWER(p.per_nombre) = LOWER('$nomPerfilSes') AND pm.pm_modulo = 'usuarios' LIMIT 1");
			if ($rMod2 && mysqli_num_rows($rMod2) > 0) { $puedeGestionarUsuarios = true; }
		}
	}
	if (!$puedeGestionarUsuarios) {
		header("location: ../../main.php?module=dashboard");
		exit;
	}
	$soyAdminTotal = esSuperAdmin($mysqli);

	// Perfil "Super Admin": solo otro Super Admin puede asignarlo ni tocar una cuenta que ya lo tenga.
	$esPerfilSuperAdmin = function ($per_id) use ($mysqli) {
		$per_id = (int)$per_id;
		$r = mysqli_query($mysqli, "SELECT per_nombre FROM perfil WHERE per_id = $per_id");
		$f = $r ? mysqli_fetch_assoc($r) : null;
		return $f && strtolower($f['per_nombre']) === 'super admin';
	};
	$volverConError = function ($msg) {
		echo "<script>alert('" . addslashes($msg) . "'); history.back();</script>";
		exit;
	};
	// Usuario: mismos caracteres que acepta login-check.php (si no, la cuenta quedaría inservible)
	// y único (con duplicados, el login entra siempre a la primera cuenta que encuentra).
	$validarUsuarioNombre = function ($nombre, $id_excluir) use ($mysqli, $volverConError) {
		if ($nombre === '' || !preg_match('/^[\p{L}\p{N}_.\-]+$/u', $nombre)) {
			$volverConError('El usuario solo puede tener letras, números, guion, guion bajo y punto, sin espacios.');
		}
		$n = mysqli_real_escape_string($mysqli, $nombre);
		$ex = (int)$id_excluir;
		$q = mysqli_query($mysqli, "SELECT id_user FROM usuario WHERE username = '$n' AND id_user <> $ex LIMIT 1");
		if ($q && mysqli_num_rows($q) > 0) {
			$volverConError('Ya existe un usuario con ese nombre de usuario.');
		}
	};

	if ($_GET['act']=='insert') {
		if (isset($_POST['Guardar'])) {
	
			$validarUsuarioNombre(trim($_POST['username']), 0);
			$username    = mysqli_real_escape_string($mysqli, trim($_POST['username']));
			$password    = md5(mysqli_real_escape_string($mysqli, trim($_POST['password'])));
			$name_user   = mysqli_real_escape_string($mysqli, trim($_POST['name_user']));
			agregarColumnaSiNoExiste($mysqli, 'usuario', 'documento', 'VARCHAR(20) NULL');
			$documento_t = mysqli_real_escape_string($mysqli, trim($_POST['documento'] ?? ''));
			$documento_val = $documento_t !== '' ? "'$documento_t'" : 'NULL';
			$per_id_form = (int)($_POST['per_id'] ?? 0);
			if (!$soyAdminTotal && $esPerfilSuperAdmin($per_id_form)) {
				$volverConError('Solo un Super Admin puede crear cuentas con el perfil Super Admin.');
			}
			$cli_id_val  = !empty($_POST['cli_id']) ? (int)$_POST['cli_id'] : 'NULL';
			$loc_id_val  = !empty($_POST['loc_id']) ? (int)$_POST['loc_id'] : 'NULL';

			// Derive permisos_acceso from profile name (session compatibility)
			$pr_res = mysqli_query($mysqli, "SELECT per_nombre FROM perfil WHERE per_id=$per_id_form AND per_activo=1");
			$pr_row = $pr_res ? mysqli_fetch_assoc($pr_res) : null;
			$permisos_acceso = $pr_row ? mysqli_real_escape_string($mysqli, $pr_row['per_nombre']) : '';

            $query = mysqli_query($mysqli, "INSERT INTO usuario(username,password,name_user,documento,permisos_acceso,per_id,cli_id,loc_id)
                                            VALUES('$username','$password','$name_user',$documento_val,'$permisos_acceso',$per_id_form,$cli_id_val,$loc_id_val)");

            if ($query) {
                header("location: ../../main.php?module=usuarios&alert=1");
                exit;
            } else {
                echo "<script>alert('Error al crear usuario: " . addslashes(mysqli_error($mysqli)) . "'); history.back();</script>";
            }
		}
	}
	
	elseif ($_GET['act']=='update') {
		if (isset($_POST['Guardar'])) {
			if (isset($_POST['id_user'])) {
				$id_user     = mysqli_real_escape_string($mysqli, trim($_POST['id_user']));
				$validarUsuarioNombre(trim($_POST['username']), (int)$id_user);
				if (!$soyAdminTotal) {
					$rObj = mysqli_query($mysqli, "SELECT per_id FROM usuario WHERE id_user = " . (int)$id_user);
					$fObj = $rObj ? mysqli_fetch_assoc($rObj) : null;
					if (($fObj && $esPerfilSuperAdmin($fObj['per_id'])) || $esPerfilSuperAdmin($_POST['per_id'] ?? 0)) {
						$volverConError('Solo un Super Admin puede modificar cuentas con el perfil Super Admin.');
					}
				}
				$username    = mysqli_real_escape_string($mysqli, trim($_POST['username']));
				$password    = md5(mysqli_real_escape_string($mysqli, trim($_POST['password'])));
				$name_user   = mysqli_real_escape_string($mysqli, trim($_POST['name_user']));
				$email       = mysqli_real_escape_string($mysqli, trim($_POST['email']));
				$telefono    = mysqli_real_escape_string($mysqli, trim($_POST['telefono']));
				agregarColumnaSiNoExiste($mysqli, 'usuario', 'documento', 'VARCHAR(20) NULL');
				$documento_t = mysqli_real_escape_string($mysqli, trim($_POST['documento'] ?? ''));
				$documento_val = $documento_t !== '' ? "'$documento_t'" : 'NULL';
				$per_id_form = (int)($_POST['per_id'] ?? 0);
				$cli_id_val  = !empty($_POST['cli_id']) ? (int)$_POST['cli_id'] : 'NULL';
				$loc_id_val  = !empty($_POST['loc_id']) ? (int)$_POST['loc_id'] : 'NULL';

				// Derive permisos_acceso from profile name (session compatibility)
				$pr_res = mysqli_query($mysqli, "SELECT per_nombre FROM perfil WHERE per_id=$per_id_form AND per_activo=1");
				$pr_row = $pr_res ? mysqli_fetch_assoc($pr_res) : null;
				$permisos_acceso = $pr_row ? mysqli_real_escape_string($mysqli, $pr_row['per_nombre']) : '';
				
				$name_file          = $_FILES['foto']['name'];
				$ukuran_file        = $_FILES['foto']['size'];
				$tipe_file          = $_FILES['foto']['type'];
				$tmp_file           = $_FILES['foto']['tmp_name'];
				
		
				$allowed_extensions = array('jpg','jpeg','png');
				
			
				$path_file          = "../../images/user/".$name_file;
		
				$file               = explode(".", $name_file);
				$extension          = array_pop($file);

				if (empty($_POST['password']) && empty($_FILES['foto']['name'])) {
					
                    $query = mysqli_query($mysqli, "UPDATE usuario SET username='$username',
                                                                  name_user='$name_user',
                                                                  email='$email',
                                                                  telefono='$telefono',
                                                                  documento=$documento_val,
                                                                  permisos_acceso='$permisos_acceso',
                                                                  per_id=$per_id_form,
                                                                  cli_id=$cli_id_val,
                                                                  loc_id=$loc_id_val
                                                                  WHERE id_user='$id_user'")
                                                    or die('error: '.mysqli_error($mysqli));

                
                    if ($query) {
                  
                        header("location: ../../main.php?module=usuarios");
                    }
				}
		
				elseif (!empty($_POST['password']) && empty($_FILES['foto']['name'])) {

                    $query = mysqli_query($mysqli, "UPDATE usuario SET username='$username',
                                                                  name_user='$name_user',
                                                                  password='$password',
                                                                  email='$email',
                                                                  telefono='$telefono',
                                                                  documento=$documento_val,
                                                                  permisos_acceso='$permisos_acceso',
                                                                  per_id=$per_id_form,
                                                                  cli_id=$cli_id_val,
                                                                  loc_id=$loc_id_val
                                                                  WHERE id_user='$id_user'")
                                                    or die('error : '.mysqli_error($mysqli));

             
                    if ($query) {
                    
                        header("location: ../../main.php?module=usuarios");
                    }
				}
				
				elseif (empty($_POST['password']) && !empty($_FILES['foto']['name'])) {
			
					if (in_array($extension, $allowed_extensions)) {
	                
	                    if($ukuran_file <= 1000000) { 
	                        
	                        if(move_uploaded_file($tmp_file, $path_file)) { 
                        		
			                    $query = mysqli_query($mysqli, "UPDATE usuario SET username='$username',
			                                                                  name_user='$name_user',
			                                                                  email='$email',
			                                                                  telefono='$telefono',
			                                                                  documento=$documento_val,
			                                                                  foto='$name_file',
			                                                                  permisos_acceso='$permisos_acceso',
			                                                                  per_id=$per_id_form,
			                                                                  cli_id=$cli_id_val,
			                                                                  loc_id=$loc_id_val
			                                                                  WHERE id_user='$id_user'")
			                                                    or die('error : '.mysqli_error($mysqli));

			                    if ($query) {
			                    
			                        header("location: ../../main.php?module=usuarios&alert=2");
			                    }
                        	} else {
	                           
	                            header("location: ../../main.php?module=ususairos&alert=5");
	                        }
	                    } else {
	                       
	                        header("location: ../../main.php?module=usuarios&alert=6");
	                    }
	                } else {
	                   
	                    header("location: ../../main.php?module=usuarios&alert=7");
	                } 
				}
				
				else {
					
					if (in_array($extension, $allowed_extensions)) {
	                   
	                    if($ukuran_file <= 1000000) { 
	                       
	                        if(move_uploaded_file($tmp_file, $path_file)) { 
                        		
			                    $query = mysqli_query($mysqli, "UPDATE usuario SET username='$username',
			                                                                  name_user='$name_user',
			                                                                  password='$password',
			                                                                  email='$email',
			                                                                  telefono='$telefono',
			                                                                  documento=$documento_val,
			                                                                  foto='$name_file',
			                                                                  permisos_acceso='$permisos_acceso',
			                                                                  per_id=$per_id_form,
			                                                                  cli_id=$cli_id_val,
			                                                                  loc_id=$loc_id_val
			                                                                  WHERE id_user='$id_user'")
			                                                    or die('error: '.mysqli_error($mysqli));

			                    
			                    if ($query) {
			                       
			                        header("location: ../../main.php?module=usuario&alert=2");
			                    }
                        	} else {
	                            
	                            header("location: ../../main.php?module=usuario&alert=5");
	                        }
	                    } else {
	                       
	                        header("location: ../../main.php?module=usuario&alert=6");
	                    }
	                } else {
	                
	                    header("location: ../../main.php?module=usuario&alert=7");
	                } 
				}
			}
		}
	}


	elseif ($_GET['act']=='on') {
		if (isset($_GET['id'])) {
			
			$id_user = $_GET['id'];
			$status  = "activo";

		
            $query = mysqli_query($mysqli, "UPDATE usuario SET status  = '$status'
                                                          WHERE id_user = '$id_user'")
                                            or die('error: '.mysqli_error($mysqli));

  
            if ($query) {
               
                echo "updated";
            }
		}
	}


	elseif ($_GET['act']=='off') {
		if (isset($_GET['id'])) {
			
			$id_user = $_GET['id'];
			$status  = "bloqueado";

		
            $query = mysqli_query($mysqli, "UPDATE usuario SET status  = '$status'
                                                          WHERE id_user = '$id_user'")
                                            or die('Error : '.mysqli_error($mysqli));

        
            if ($query) {
              
                echo "updated";
            }
		}
	}		
}		
?>
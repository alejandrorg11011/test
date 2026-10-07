<?php

	// includes/requires *******************************************************************
		$rutaInclude = "../include/php/";
		require($rutaInclude."configuracion.php");
		includesPHP($rutaInclude);

		require_once("../include/PHPMailer_5.2.4/class.phpmailer.php");
		
		include '../vendor/apiRedsys.php';

	// establecer variables *****************************************************************
		$seccion = "inscripcion";
		$pagina_enlace = "inscripcion";
		$titulo_seccion = "Inscripción " . constClienteCorto;
		$tablaBD = constTBD_inscripciones;
		$existe_nif = false;
		$existe_mail = false;

	// si están intentando reenviar formulario (F5) o no hay datos del formulario redireccionamos 
		if (evitar_ReenviarFormulario($_POST["HpostID"]) || $_POST["HpostID"]=='')
		{
			header("Location: ".$pagina_enlace.".php");
			exit();
		}

	// recoger variables ****************************************************************
		$_POST = str_replace("'","´",$_POST); // sustituimos la comilla simple para evitar errores SQL
		// datos asistente

			$nombre = addslashes(trim($_POST["Tnombre"]));
			$apellidos = addslashes(trim($_POST["Tapellidos"]));
			$nombre_completo = $nombre." ".$apellidos;
			
			$mail = strtolower(trim($_POST["Tmail"]));
			$mail2 = strtolower(trim($_POST["Tmail2"]));
			
			$mail2 = ($mail2!=$mail?$mail2:"");

			
			$nif = strtoupper(trim($_POST["Tnif"]));
			if( empty($nif) )
				$nif = "111111111A";
			
			$direccion = addslashes(trim($_POST["Tdireccion"])); // quito addslashes por la comilla Vall d'hebron
			$ciudad = addslashes(trim($_POST["Tciudad"])); // quito addslashes por la comilla Vall d'hebron
			$poblacion = addslashes(trim($_POST["Tpoblacion"])); // quito addslashes por la comilla Vall d'hebron
			$provincia = addslashes($_POST["Sprovincia"]);
			if( empty( $provincia ) )
				$provincia = 0;
			// xrg
			// 150419: ampliacion sin terminar. Recoge el nuevo pais, pero no lo puedo
			// introducir en la tabla provincias porque me da error la conexion $link.
			// Por no perder mas tiempo hago que se muestre en el email y por lo menos
			// queda ahi reflejado.
			if ( $_POST["otroPais"] != "" )
				$otroPais = $_POST["otroPais"];
			//*************************************************************************

			$codigo_postal = addslashes(trim($_POST["Tcp"]));
			$movil = addslashes(trim($_POST["Tmovil"]));
			$telefono = addslashes(trim($_POST["Ttelefono"]));
			$telefono = ($telefono!=$movil?$telefono:"");
			//$fax = addslashes(trim($_POST["Tfax"]));
			$ccaa = $_POST["Sccaa"];
			$centro_trabajo = $_POST["Scentrotrabajo"];
			
			$pais_ct = $_POST["pais_ct"];
			$centro_trabajo2 = $_POST["centro_trabajo2"];


			$provincia_ct = $_POST["Sprovinciacentrotrabajo"];
			$cargo = addslashes(trim($_POST["Tcargo"]));

			$categoria_profesional = addslashes(trim($_POST["Tcategoria_profesional"]));

			$comentarios = addslashes(trim($_POST["Tcomentarios"]));

			$pais = $_POST["pais"];

		// datos logisticos
			$asistencia0 = $_POST["checkAsistencia0"];
			$asistencia1 = $_POST["checkAsistencia1"];
		
		//datos cuota
			//$cuota = addslashes($_POST["Rcuota"]);
			$tipo_cuota = 1;
			$precio_cuota = 0;
			if (!constInscripcion_gratuita)
			{
				$cuota = $_POST["Rcuota"];
				$array_cuota = explode("#",$cuota);
				$tipo_cuota = $array_cuota[0];
				$precio_cuota = $array_cuota[1];
			}

			$cena = $_POST["Ccena"];
			if (!isset($cena)) $cena='No'; //si el check no se ha marcado lo ponemos a No
		// datos facturación
			$fac_nombre = addslashes(trim($_POST["Tnombrefac"]));
			$fac_nif = addslashes(trim($_POST["Tniffac"]));
			$fac_direccion = addslashes(trim($_POST["Tdireccionfac"]));
			$fac_codigo_postal = addslashes(trim($_POST["Tcpfac"]));
			$fac_provincia = addslashes($_POST["Sprovinciafac"]);
			$fac_localidad = addslashes(trim($_POST["Tlocalidadfac"]));
			$fac_mail = addslashes(trim($_POST["Tmailfac"]));

			//xrg - FORMAS DE PAGO
			$metodo_pago = $_POST["metodo_pago"];
			$activo = 'No';
			$clave_app = numeroaleatorio(constLongitudClaveAPP);
			/***************** REDSYS ************************/
			/************************************************/
				$redsys = new RedsysAPI;

				// Valores de entrada que no hemos cmbiado para ningun ejemplo
				$fuc = "133550095";
				$terminal = "1";
				$moneda = "978";
				$trans = "0";
				$url = _HOME_ ;
				$urlKO = _HOME_ . "inscripcion/pago_denegado.php?d=" . base64_encode( $nif );
				$urlOK = _HOME_ . "inscripcion/pago_confirmado.php?d=" . base64_encode( $nif );
				$id = $clave_app;
				$amount = $precio_cuota . "00";	
				$description = constClienteBreve . " - NIF " . $nif;//se muestra en pantalla y se registra en la orden.
				$titular = $nombre_completo;//se muestra en pantalla pero NO se registra en la orden
				
				// Se Rellenan los campos
				$redsys->setParameter("DS_MERCHANT_AMOUNT",$amount);
				$redsys->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION",$description);
				$redsys->setParameter("DS_MERCHANT_TITULAR",$titular);
				$redsys->setParameter("DS_MERCHANT_ORDER",$id);
				$redsys->setParameter("DS_MERCHANT_MERCHANTCODE",$fuc);
				$redsys->setParameter("DS_MERCHANT_CURRENCY",$moneda);
				$redsys->setParameter("DS_MERCHANT_TRANSACTIONTYPE",$trans);
				$redsys->setParameter("DS_MERCHANT_TERMINAL",$terminal);
				$redsys->setParameter("DS_MERCHANT_MERCHANTURL",$url);
				$redsys->setParameter("DS_MERCHANT_URLOK",$urlOK);
				$redsys->setParameter("DS_MERCHANT_URLKO",$urlKO);

				// var_dump( $redsys );
				//Datos de configuración
				$version="HMAC_SHA256_V1";

				$kc = 'F1wA1ghCdimC+yHzLSebid3EZGlV3ys7';//REAL
				// $kc = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';// TEST
				
				// Se generan los parámetros de la petición
				$request = "";
				$params = $redsys->createMerchantParameters();
				$signature = $redsys->createMerchantSignature($kc);

				// ACTION
				$action = "https://sis.redsys.es/sis/realizarPago"; // REAL
				// $action = "https://sis-t.redsys.es:25443/sis/realizarPago"; // TEST

			/***************** FIN REDSYS ************************/
			/************************************************/



	// FIN recoger variables ****************************************************************

	// comprobamos que no existe un usuario registrado con el mismo nif o con el mismo mail (el mail no lo controlamos porque a veces ponen el mismo para varias personas) ***************
			$conn = ConectarBD();
			$array = ObtenerRegistrosBD($tablaBD,"nif","nif='".$nif."'","nif",$conn);
			if (count($array)>0)
			{
				//$envio_correcto = false;
				$existe_nif = true;
			}
			
			$array = ObtenerRegistrosBD($tablaBD,"mail","mail='".$mail."'","mail",$conn);
			if (count($array)>0)
			{
				//$envio_correcto = false;
				$existe_mail = true;
			}
			if (!$existe_nif && !$existe_mail )
			{
				// guardamos los datos en la tabla de la BD
					// conectamos a la BD
					$mySQL = "INSERT INTO ".$tablaBD." ";
					$mySQL.= "(";
					// datos personales
						$mySQL.= "nif";
						$mySQL.= ", fecha_inscripcion";
						$mySQL.= ", nombre";
						$mySQL.= ", apellidos";
						$mySQL.= ", mail";
						$mySQL.= ", direccion";
						$mySQL.= ", poblacion";
						$mySQL.= ", codigo_postal";
						$mySQL.= ", provincia";
						$mySQL.= ", telefono";
						$mySQL.= ", mail2";
						$mySQL.= ", movil";
						$mySQL.= ", cargo";
						$mySQL.= ", categoria_profesional";
						$mySQL.= ", centro_trabajo";
						$mySQL.= ", centro_trabajo2";
						$mySQL.= ", provincia_ct";
						$mySQL.= ", comentarios";
						$mySQL.= ", clave_app";
						$mySQL.= ", activo";

					// datos cuota
						$mySQL.= ", tipo_cuota";
						$mySQL.= ", precio_cuota";
						$mySQL.= ", cena";

					// datos logisticos
						$mySQL.= ", asistencia0";
						$mySQL.= ", asistencia1";

					// datos facturacion
						$mySQL.= ", fac_nombre";
						$mySQL.= ", fac_nif";
						$mySQL.= ", fac_direccion";
						$mySQL.= ", fac_codigo_postal";
						$mySQL.= ", fac_provincia";
						$mySQL.= ", fac_localidad";
						$mySQL.= ", fac_mail";
						$mySQL.= ", metodo_pago";
						$mySQL.= ", pais";
					$mySQL.= ")";
					$mySQL.= " VALUES (";
					// datos personales
						$mySQL.= "UPPER('".$nif."')";
						$mySQL.= ",'".date('Y-m-d H:i:s')."'";
						$mySQL.= ",'".$nombre."'";
						$mySQL.= ",'".$apellidos."'";
						$mySQL.= ",'".$mail."'";
						$mySQL.= ",'".$direccion."'";
						$mySQL.= ",'".$poblacion."'";
						$mySQL.= ",'".$codigo_postal."'";
						$mySQL.= ", ".$provincia;
						$mySQL.= ",'".$telefono."'";
						$mySQL.= ",'".$mail2."'";
						$mySQL.= ",'".$movil."'";
						$mySQL.= ",'".$cargo."'";
						$mySQL.= ",'".$categoria_profesional."'";
						$mySQL.= ",'".$centro_trabajo."'";
						$mySQL.= ",'".$centro_trabajo2."'";
						$mySQL.= ",'".$provincia_ct."'";
						$mySQL.= ",'".$comentarios."'";
						$mySQL.= ",'".$clave_app."'";
						$mySQL.= ",'".$activo."'";

					// datos cuota
						$mySQL.= ",".$tipo_cuota;
						$mySQL.= ",".$precio_cuota;
						$mySQL.= ",'".$cena."'";

					// datos logisticos
						$mySQL.= ",'".$asistencia0."'";
						$mySQL.= ",'".$asistencia1."'";

					// datos facturacion
						$mySQL.= ",'".$fac_nombre."'";
						$mySQL.= ", UPPER('".$fac_nif."')";
						$mySQL.= ",'".$fac_direccion."'";
						$mySQL.= ",'".$fac_codigo_postal."'";
						$mySQL.= ", ".($fac_provincia==0?"NULL":"'".$fac_provincia."'");
						$mySQL.= ",'".$fac_localidad."'";
						$mySQL.= ",'".$fac_mail."'";

						$mySQL.= ",'".$metodo_pago."'";

						$mySQL.= ",'".$pais."'";
					$mySQL.= ")";
					// echo $mySQL;

					$result = mysqli_query($conn, $mySQL);
				
				// $result = true;

					if ($result) //se ha insertado correctamente
					{
						include_once("../configuration/settings_mail.php");

						$mailer->From = constMailContacto;
						$mailer->FromName = constClienteCorto;

					    $receptor = constMailContacto_soporte;
					    $receptor2 = $_POST["Tmail"];
					    $receptor3 = constMailContacto;

					    // $mailer->AddBCC( $receptor); 
					    
					    $mailer->AddAddress( $receptor2 );
					    $mailer->AddAddress( $receptor3 );

					    if ( $_POST["Tmail2"] != ""){

					    	$mailer->AddAddress( $_POST["Tmail2"]);
					    }

					    if ( $_POST["Tmailfac"] != ""){

					    	$mailer->AddAddress( $_POST["Tmailfac"]);
					    }
					    
					    // $mailer->AddAddress("informatica@ferpuser.com");
	                    
	                    
	                    $mailer->AddReplyTo( constMailContacto, "Sanicongress" );
					    
					    $mailer->Subject = $nombre_completo . " Inscripcion: " . constClienteCorto; 


						$cuerpo = correo("cabecera");
						$cuerpo .= correo("body_inicio");
						$cuerpo .= correoInicio_Cuerpo($titulo_seccion,$nombre_completo,1);

						$cuerpo .= correoDatosAsistente();
						$cuerpo .= correoDatosCentroTrabajo();
						if (!constInscripcion_gratuita)
						{
							$cuerpo .= correoDatosCuota();
							//$cuerpo .= correoDatosLogisticos();
							//$cuerpo .= correoDatosTalleres();
							$cuerpo .= correoDatosFacturacion();
						}
						$cuerpo .= correoNota($titulo_seccion);
						$cuerpo .= correo("pie");

					    $mailer->Body =  acentosToHTML( $cuerpo );


						$mailer->Send(); 

						$mailer->ClearAddresses();

					}

			}
			
?>
<?php include HEADER; ?>

<?php include HEADER_BODY ?>

<?php include LEFTCOL ?>

                <!-- CENTRAL COLUMN -->
                <div class="<?= CLASS_CENTRAL_COLUMN ?>">
                    <div class="contenido-centro">


				
                    <h2><?php p( _t( $titulo_seccion) ); ?></h2>
                
				<div id="<?= $seccion ?>">
					<?
					if( $result )
					{

						?>
	                    	<div>
	                    		<p style="color:red"><strong>Para completar la inscripción debe realizar el pago pulsando en el siguiente botón:</strong></p>

	                    		<form name="frm" action="<?= $action ?>" method="POST">

	                    			<input type="hidden" name="Ds_SignatureVersion" value="<?php echo $version; ?>"/>

	                    			<input type="hidden" name="Ds_MerchantParameters" value="<?php echo $params; ?>"/>

	                    			<input type="hidden" name="Ds_Signature" value="<?php echo $signature; ?>"/>
	                    			
	                    			<h4 class="text-center">
	                    				<button class="btn btn-default"><strong>REALIZAR PAGO</strong></button>
	                    			</h4>
	                    			
	                    			<p><strong>(Una vez realizado el pago, debe pulsar el botón "CONTINUAR" para volver a la web del congreso)</strong></p>
	                    		</form>
	                    	
	                    	</div>
	                        
						<?
						
					}
					else
					{
						if ($existe_nif || $existe_mail)
						{
							if ($existe_nif)
							{
							?>
                            <div id="error_accion" class="centrado" style="margin-top:2em">
                            	<p>Ya existe un usuario inscrito con el NIF/NIE indicado: <strong><?= $nif ?></strong></p>
	                            <p>Dudas o incidencias: <a href="mailto:<?= constMailContacto?>"><?= constMailContacto ?></a></p>
                            </div>
                            <?
							}
							elseif ($existe_mail)
							{
							?>
								<p class="error" style="margin-top:2em">
									Ya existe un usuario inscrito con el e-mail indicado: <strong><?= $mail ?></strong></p>
                            <?
							}
							?>   
	                            <p>Dudas o incidencias con la inscripción: <a href="mailto:<?= constMailContacto?>"><?= constMailContacto ?></a></p>
						<?
						}
						else
						{
						?>
                            <div id="error_accion" class="centrado" style="margin-top:2em">
                                <p>Se ha producido un error en el envío del formulario</p>
                                <p>Pruebe m&aacute;s tarde. Si el problema persiste p&oacute;ngase en contacto con <a href="mailto:<?= constMailContacto ?>"><?= constMailContacto ?></a></p>
                            </div>
						<?
						}
						?>
                    <?						
					}
					?>
                    <p class="centrado pull-right" style="margin-top:2em">
                        <? //= botones_imagen(constURLWebIconos."volver.png",constURLWebIconos."volver2.png","Volver",$_SERVER['HTTP_REFERER'],"","url"); ?>
                        <a href="<?= $_SERVER['HTTP_REFERER'] ?>" class="btn btn-default"><?php _t("Volver")?></a>
                    </p>

                </div>

                    </div>
                </div>
                <!-- END CENTRAL COLUMN -->


<?php include RIGHTCOL ?>

<?php include FOOTER ?>
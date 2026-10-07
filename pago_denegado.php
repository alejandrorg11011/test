<?php 

$rutaInclude = "../include/php/";
require($rutaInclude."configuracion.php");
includesPHP($rutaInclude);
require_once("../include/PHPMailer_5.2.4/class.phpmailer.php");
include_once("../configuration/settings_mail.php");

    if( isset( $_GET["d"] ) ){

        $nif = base64_decode( $_GET['d'] );
    }
        
    $datos = getDatos("SELECT * FROM " . constTBD_inscripciones . " WHERE nif = '" . $nif . "' ");

    $nombre_completo = $datos[0]["nombre"] . " " . $datos[0]["apellidos"];

    $mailer->From = constMailContacto;
    $mailer->FromName = constClienteCorto;

    $receptor = constMailContacto_soporte;
    $receptor2 = $datos[0]["mail"];
    $receptor3 = constMailContacto;

    $mailer->AddBCC( $receptor); 
    $mailer->AddAddress( $receptor2 );
    $mailer->AddAddress( $receptor3 );

    // $mailer->AddAddress( "informatica@ferpuser.com" );

    
    $mailer->Subject = $nombre_completo.": Inscripción. Pago denegado."; 

    $cuerpo = correo("cabecera");
    $cuerpo .= correo("body_inicio");

    $cuerpo .= "Pago denegado a " . $nombre_completo;


    $cuerpo .= correo("pie");

    $mailer->Body = acentosToHTML( $cuerpo );


    if ( !$mailer->Send() ) {

      echo "Mailer Error: " . $mailer->ErrorInfo . " Destinatario: " . $receptor . "<br/>";

    } 
    $mailer->ClearAddresses();


?>

<?php include HEADER; ?>

<?php include HEADER_BODY ?>

<?php include LEFTCOL ?>

                <!-- CENTRAL COLUMN -->
                <div class="<?= CLASS_CENTRAL_COLUMN ?>">
                    <div class="contenido-centro">

						<h1>Pago denegado</h1>

                    	<!-- <div class="nota_sin_fondo">

                            <p class="izquierda">Para cualquier modificaci&oacute;n o cancelaci&oacute;n remita un correo electr&oacute;nico a <a href="mailto:<?= constMailContacto ?>"><?= constMailContacto ?></a></p>
                            
                            <p>Agradeciendo de antemano su confianza en nosotros, reciba un cordial saludo.</p>
                        </div> -->


	                </div>
                </div>
                <!-- END CENTRAL COLUMN -->


<?php include RIGHTCOL ?>

<?php include FOOTER ?>
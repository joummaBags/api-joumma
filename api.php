<?php
/*
*
*   API: JOUMMA BAGS
*   CMS: PRESTASHOP 1.6
*   
*   DESCRIPCION:
*       Actualiza los stocks de la base de datos.
*       Usa la gestion avanzada de stock de prestashop.
*
*   
*
*/

include('include/db.php');

$API=array();$API['urls']=array();


/*
*
*   DATOS DE CONEXION NECESARIOS. KEY API Y URL DE ACCESO.
*
*/
$API['KEY']='{YOUR KEY API}';     //KEY API.
//Old Version <2019
//$API['urls']['STOCK']='http://joumma.com/shop/api/{YOUR KEY API}/productos.php?jsa=1&api={YOUR KEY API}';
//New Version 2019
$API['urls']['STOCK']='http://api.joumma.com:8080/?keyapi={YOUR KEY API}';        //URL DE LA SINCRONIZACION DE STOCK.

// ------------------------- FIN : DATOS DE ACCESO. --------------------------------


/*
*
*   INFORMACION EN PANTALLA SOBRE EL ACCESO.
*
*/
    imprimir('  J O U M M A    B A G S    *API*');
    imprimir('');
    imprimir('_________________________________');
    imprimir('|  ');
    imprimir('|  ');
    imprimir('| 1: CARGANDO');
    imprimir('|  *');
    

$db= new _BD(); 
    
    imprimir('|  ***** Base de datos: OK');
    imprimir('|');
    imprimir('|________________________________');
    imprimir('');
    imprimir('');

//$REFERENCIAS = bloques que se descargaran
$REFERENCIAS=20;$rTemp=0;$fin=false;
    imprimir(' 2: CONECTANDO A JOUMMA.COM: OK');
    imprimir('  *');
    imprimir('  ***** CARGANDO BLOQUES DE '.$REFERENCIAS);
    

for($i=0;$i>=0;$i++){
    
    imprimir('  *');
    imprimir('  ******** BLOQUE '.($i+1));
    imprimir('  * *');
    imprimir('  * *** CONECTANDO A JOUMMA.COM');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $API['urls']['STOCK'].'&limite_inicio='.$rTemp.'&limite_fin='.$REFERENCIAS);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($ch);
    curl_close($ch);
    
    
    $JSON_DECODIFICADO = json_decode($json);
    $id_del_almacen=4;

    
    for($ii=0;$ii<count($JSON_DECODIFICADO);$ii++){
    
        imprimir('  * *');
        imprimir('  * ******** REFERENCIA: '.$JSON_DECODIFICADO[$ii]->id_producto);
        imprimir('  * * '.($ii+1).'/'.count($JSON_DECODIFICADO));
        
        $sql='SELECT * FROM '.$db->prefix.'stock WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'" and id_warehouse="'.$id_del_almacen.'"';
        $resultado = $db->_db_consulta($sql);

        //BUSCAMOS EL PRODUCTO EN LA BASE DE DATOS.
        if($resultado->num_rows >0){
            imprimir('  * * EXISTE EN LA TABLA STOCK');   
            
            //ACTUALIZAMOS EL STOCK DEL PRODUCTO.
            $sql='UPDATE '.$db->prefix.'stock SET pysical_quanty="'.$JSON_DECODIFICADO[$ii]->cantidad.'", usable_quanty="'.$JSON_DECODIFICADO[$ii]->cantidad.'" WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'" and id_warehouse="'.$id_del_almacen.'"';
            $resultado = $db->_db_consulta($sql);
            
            imprimir('  * * ACTUALIZADO: OK');
            
            
        }else{
            imprimir('  * * NO EXISTE.');
            imprimir('  * * BUSCANDO PRODUCTO.');

           

            $pasador=false;
            $Referencia1='';
            $Referencia2='0';

            if($pasador==false){

                $sql='SELECT * FROM '.$db->prefix.'product WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'"';
                $resultado = $db->_db_consulta($sql);

                if($resultado->num_rows>0){

                    $Referencia1=$resultado->row['id_product'];
                    $pasador=true;
                    
                }
            }

            if($pasador==false){

                $sql='SELECT * FROM '.$db->prefix.'product_attribute WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'"';
                $resultado = $db->_db_consulta($sql);

                if($resultado->num_rows>0){

                    $Referencia1=$resultado->row['id_product'];
                    $Referencia2=$resultado->row['id_product_attribute'];
                    $pasador=true;
                    
                }
            }

            if($pasador==true){

                $sql='SELECT * FROM '.$db->prefix.'product WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'"';
                $resultado = $db->_db_consulta($sql);
                $db->_db_consulta('INSERT INTO '.$db->prefix.' (id_warehouse,id_product,id_product_attribute,reference,ean13,physical_quantity,usable_quantity,price_te) VALUES ("'.$id_del_almacen.'","'.$Referencia1.'","'.$Referencia2.'","'.$JSON_DECODIFICADO[$ii]->id_producto.'","","'.$JSON_DECODIFICADO[$ii]->cantidad.'","'.$JSON_DECODIFICADO[$ii]->cantidad.'","")');
                
                imprimir('  * * CREADO...');
            }else{
                imprimir('  * * NO EXISTE.');
            }

        }
        
    }
    imprimir('  ******* FIN DEL BLOQUE '.($i+1));
    
    
    if(count($JSON_DECODIFICADO)!= $REFERENCIAS){
        break;
    }
    
    
    
    $rTemp=$rTemp+$REFERENCIAS+1;
}


    


function imprimir($texto){

    echo $texto;
    echo "\n";
}

?>

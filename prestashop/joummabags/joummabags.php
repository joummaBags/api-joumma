<?php
    
    if(!defined('_PS_VERSION_')){ exit(); }
    
    class joummabags extends Module{

        public function __construct(){

            $this->name = 'joummabags';
            $this->tab = 'front_office_features';
            $this->version = '1.0.0';
            $this->author ='JosAlba';
            $this->need_instance = 0;
            $this->ps_versions_compliancy = array('min' => '1.6.x.x', 'max' => _PS_VERSION_);

            $config = Configuration::getMultiple ( array (
                'joumma_almacen',
                'joumma_keyapi',
                'joumma_stock',
                'joumma_precio'
            ) );

            parent::__construct();

            $this->displayName = $this->l('Joumma bags');
            $this->description = $this->l('Sincroniza el stock');
            $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar el módulo?');
        }

        public function install(){

            
            if(!parent::install()
            ){
                return false;
            }
            
            return true;
        }
        public function uninstall(){

            if(!parent::uninstall() || !$this->unregisterHook('displayFooterProduct')){
                return false;
            }
                 
            return true;
        }
        public function getContent(){

            $output = null;
            if (Tools::isSubmit('submit'.$this->name)){

                Configuration::updateValue('joumma_almacen',    Tools::getValue('joumma_almacen'));
                Configuration::updateValue('joumma_keyapi',     Tools::getValue('joumma_keyapi'));
                Configuration::updateValue('joumma_stock',      Tools::getValue('stock_ACTIVO'));
                Configuration::updateValue('joumma_precio',     Tools::getValue('precio_ACTIVO'));

                $output .= $this->displayConfirmation($this->l('Settings updated'));
                
            }

            return $output.$this->displayForm();

        }
        public function displayForm(){
            $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

            /**
             * Url para lanzar en el cron.
             */
            $urlCront = Context::getContext()->shop->getBaseURL(true);
            $urlCront = $urlCront.'modules/joummabags/sync.php';

            $fields_form = array();
            $fields_form[0]['form'] = array(
                'legend' => array(
                    'title' => $this->l('jPresent'),
                    'icon'  => 'icon-folder-close'
                ),
                'input' => array(
                    array(
                        'type' => 'html',
                        'html_content' => '<strong>Configuracion</strong><br>',
                        'name' => 'hrseparate1',
                    ),
                    array(
                        'type'  => 'text',
                        'label' => $this->l('KeyAPI : '),
                        'name'  => 'joumma_keyapi',
                        'size'  => 20,
                        'required' => true
                    ),
                    array(
                        'type'  => 'text',
                        'label' => $this->l('ID almacen : '),
                        'name'  => 'joumma_almacen',
                        'size'  => 20,
                        'required' => true
                    ),
                    array(
                        'type' => 'html',
                        'html_content' => '<strong>CRON</strong><p>Utilizar url como cron</p><br><p><input type="text" value="'.$urlCront.'" style="width: 500px;"></p>',
                        'name' => 'hrseparate1',
                    ),
                    array(
                        'type' => 'html',
                        'html_content' => '<strong>Configuracion de sync</strong><br>',
                        'name' => 'hrseparate1',
                    ),
                    array(
                        'type'  => 'checkbox',
                        'label' => $this->l('Stock'),
                        'name'  => 'stock',
                        'values' => array(
                            'query' => $lesChoix = array(
                              array(
                                  'check_id' => 'ACTIVO',
                                  'name' => $this->l('Activo'),
                              )
                            ),
                            'id' => 'check_id',
                            'name' => 'name',
                            'desc' => $this->l('Please select')
                        )
                    ),
                    array(
                        'type'  => 'checkbox',
                        'label' => $this->l('precios/ofertas'),
                        'name'  => 'precio',
                        'values' => array(
                            'query' => $lesChoix = array(
                                array(
                                    'check_id' => 'ACTIVO',
                                    'name' => $this->l('Activo'),
                                )
                            ),
                            'id' => 'check_id',
                            'name' => 'name',
                            'desc' => $this->l('Please select')
                        )
                    )
                    

                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            );

            $helper = new HelperForm();
            // Module, token and currentIndex
            $helper->module = $this;
            $helper->name_controller = $this->name;
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

            // Language
            $helper->default_form_language = $default_lang;
            $helper->allow_employee_form_lang = $default_lang;

            // Title and toolbar
            $helper->title          = $this->displayName;
            $helper->show_toolbar   = false; 
            $helper->toolbar_scroll = false;
            $helper->submit_action  = 'submit'.$this->name;
            $helper->toolbar_btn    = array(
                'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
                'back' => array(
                    'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Back to list')
                )
            );

            $helper->fields_value['joumma_keyapi']          = Configuration::get('joumma_keyapi');
            $helper->fields_value['joumma_almacen']         = Configuration::get('joumma_almacen');
            $helper->fields_value['stock_ACTIVO']           = Configuration::get('joumma_stock');
            $helper->fields_value['precio_ACTIVO']          = Configuration::get('joumma_precio');

            return $helper->generateForm($fields_form);
        }

        /**
         * Inica la sincronizacion.
         */
        public function disparo(){

            $API=array();$API['urls']=array();
            $API['KEY']= Configuration::get("joumma_keyapi");
            $API['urls']['STOCK']='http://api.joumma.com:8080/?keyapi='.$API['KEY']; 

            /**
             * Stock
             */
            $Vstock = Configuration::get("joumma_stock");
            $id_del_almacen='';
            if($Vstock!=''){
                $id_del_almacen= Configuration::get("joumma_almacen");
            }

            /**
             * Precio
             */
            $Vprecio = Configuration::get("joumma_precio");


            $REFERENCIAS=20;$rTemp=0;$fin=false;
            /**
             * Bucle infinito para encontrar un final en las peticiones.
             */
            for($i=0;$i>=0;$i++){

                /**
                 * Peticion del bloque por curl.
                 */
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $API['urls']['STOCK'].'&limite_inicio='.$rTemp.'&limite_fin='.$REFERENCIAS);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $json = curl_exec($ch);
                curl_close($ch);

                //Recuperar el json.
                $JSON_DECODIFICADO = json_decode($json);
                
                //Bucle para recupear las lineas de productos.
                for($ii=0;$ii<count($JSON_DECODIFICADO);$ii++){

                    
                    /**
                     * Comprueba si tiene que actualizar stock.
                     */
                    if($Vstock!=''){

                        /**
                         * Comprueba si existe un almacen.
                         */
                        if($id_del_almacen!=''){
                            /**
                             * Con id de almacen
                             */

                            $resultado = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'stock` WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'" and id_warehouse="'.$id_del_almacen.'"');

                            if(count($resultado)>0){

                                Db::getInstance()->executeS('UPDATE `' . _DB_PREFIX_ . 'stock` SET pysical_quanty="'.$JSON_DECODIFICADO[$ii]->cantidad.'", usable_quanty="'.$JSON_DECODIFICADO[$ii]->cantidad.'" WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'" and id_warehouse="'.$id_del_almacen.'"');

                            }else{

                                $pasador=false;
                                $Referencia1='';
                                $Referencia2='0';

                                /**
                                 * Busca en product
                                 */
                                if($pasador==false){

                                    $resultado = Db::getInstance()->executeS('SELECT id_product FROM `' . _DB_PREFIX_ . 'product` WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'" limit 1');
                
                                    if(count($resultado)>0){
                    
                                        foreach ($carritos as $value) {
                                            $Referencia1=$value['id_product'];
                                            $pasador=true;
                                        }
                                        
                                    }
                                }
                                /**
                                 * Buscar en product_attribute
                                 */
                                if($pasador==false){

                                    $resultado = Db::getInstance()->executeS('SELECT id_product,id_product_attribute FROM `' . _DB_PREFIX_ . 'product_attribute` WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'"');
                
                                    if(count($resultado)>0){
            
                                        foreach ($carritos as $value) {

                                            $Referencia1=$value['id_product'];
                                            $Referencia2=$value['id_product_attribute'];
                                            $pasador=true;
                                        }
                                        
                                    }
                                }

                                /**
                                 * Inserta un registro en stock
                                 */
                                if($pasador==true){

                                    Db::getInstance()->executeS('INSERT INTO `' . _DB_PREFIX_ . 'stock` (id_warehouse,id_product,id_product_attribute,reference,ean13,physical_quantity,usable_quantity,price_te) VALUES ("'.$id_del_almacen.'","'.$Referencia1.'","'.$Referencia2.'","'.$JSON_DECODIFICADO[$ii]->id_producto.'","","'.$JSON_DECODIFICADO[$ii]->cantidad.'","'.$JSON_DECODIFICADO[$ii]->cantidad.'","")');                            
                                
                                }

                            }
                        
                        }else{
                            /**
                             * Sin id de almacen
                             */

                            $pasador=false;
                            $Referencia1='';
                            $Referencia2='0';

                            /**
                             * Busca en product
                             */
                            if($pasador==false){

                                $resultado = Db::getInstance()->executeS('SELECT id_product FROM `' . _DB_PREFIX_ . 'product` WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'" limit 1');
                
                                if(count($resultado)>0){
                
                                    foreach ($carritos as $value) {
                                        $Referencia1=$value['id_product'];
                                        $pasador=true;
                                    }
                                    
                                }
                            }
                            /**
                             * Buscar en product_attribute
                             */
                            if($pasador==false){

                                $resultado = Db::getInstance()->executeS('SELECT id_product,id_product_attribute FROM `' . _DB_PREFIX_ . 'product_attribute` WHERE reference="'.$JSON_DECODIFICADO[$ii]->id_producto.'"');
                
                                if(count($resultado)>0){
        
                                    foreach ($carritos as $value) {

                                        $Referencia1=$value['id_product'];
                                        $Referencia2=$value['id_product_attribute'];
                                        $pasador=true;
                                    }
                                    
                                }
                            }

                            /**
                             * Actualiza la informacion del producto.
                             */
                            if($pasador==true){

                                Db::getInstance()->executeS('UPDATE `' . _DB_PREFIX_ . 'stock_available` SET quantity="'.$JSON_DECODIFICADO[$ii]->cantidad.'" WHERE id_product="'.$Referencia1.'" and id_product_attribute="'.$Referencia2.'"');                            
                                
                            }

                        }
                    }

                    /**
                     * Comprueba si tiene que actualizar los precios.
                     */
                    if($Vprecio!=''){
                        //Pendiente.
                    }


                    // Fin/bucle de las lineas de producto.
                }

                /**
                 * Comprueba si es el final para salir del bucle.
                 */
                if(count($JSON_DECODIFICADO)!= $REFERENCIAS){
                    break;
                }

                $rTemp=$rTemp+$REFERENCIAS+1;

                // Fin/bucle infinito
            }
        }

        /**
         * Escribir en el log.
         */
        public function setLog($string){

        }
    }
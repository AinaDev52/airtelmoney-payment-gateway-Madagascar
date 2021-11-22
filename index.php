<?php
/*
 * Plugin Name: WooCommerce Airtel Payment Gateway
 * Plugin URI: https://passionprestige.mg
 * Description: Module de paiement pour Airtel money.
 * Author: Ainadev
 * Author URI: https://passionprestige.mg
 * Version: 1.0.0
 */

 /*
 * Enregistre,ent de la classe en tant que passerelle de paiement WooCommerce
 */
add_filter( 'woocommerce_payment_gateways', 'airtel_add_gateway_class' );
function airtel_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Airtel_Gateway'; // Nom de la classe
	return $gateways;
}

/*
 * La methode class
 */
add_action( 'plugins_loaded', 'airtel_init_gateway_class' );

function airtel_init_gateway_class()
{
	
    class WC_Airtel_Gateway extends WC_Payment_Gateway 
    {

        /**
 		 * Constructeur de classe
 		 */
 		public function __construct() {

            $this->id = 'airtel'; // ID du plug-in de passerelle de paiement
	        $this->icon = plugins_url( 'images/logo.png', __FILE__ ); // URL de l'icône qui sera affichée
	        $this->has_fields = false;
	        $this->method_title = 'Airtel Gateway';
	        $this->method_description = 'Module de paiement pour Airtel Money';

            // Méthode avec tous les champs d'options
	        $this->init_form_fields();

            // Chargement de la configuration
	        $this->init_settings();
	        $this->client_id = $this->get_option( 'client_id' );
	        $this->client_secret = $this->get_option( 'client_secret' );
	        $this->site_url = $this->get_option( 'site_url' );
	        $this->description = __('Payer vos achats avec Airtel Money');

            // Enregistrement des paramètres
	        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

    
        }

        /**
 		 * Options de plugin
 		 */
        public function init_form_fields(){

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Airtel Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),

                'site_url' => array(
                    'title'       => 'Lien vers le site',
                    'type'        => 'text',
                    'description' => 'Adresse url du site',
                    'default'     => home_url(),
                    'desc_tip'    => true,
                ),

                'client_id' => array(
                    'title'       => 'Client ID',
                    'type'        => 'text',
                    'description' => 'Client ID de l API.',
                    'default'     => '',
                ),

                'client_secret' => array(
                    'title'       => 'Client Secret',
                    'type'        => 'password',
                    'description' => 'Client Secret de l API.',
                    'default'     => '',
                ),
            );        
        
        }

        /**
		 * Personalisation du formulaire
		 */
		public function payment_fields() {

            if( $this->description) {
                echo wpautop( wptexturize( $this->description ) );
            }
                     
        }

        /*
		 * Implementation des fichiers de configurations
		 */
	 	public function includes() {

            require_once ('includes/config.php');
            require_once ('includes/params.php');
            require_once ('includes/payments.php');
        
        }

        /*
 		 * Validation du formulaire
		 */
        /*
		public function validate_fields() {

            // ---
    
        }
        */

        /*
		 * Traitement de paiement
		 */
		public function process_payment( $order_id ) {

            // Récupération des détails de commande
	        $order = wc_get_order( $order_id );

            /*
		     * Interaction avec l'API
		     */
            $this->includes();

	        $params = array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'site_url' => $this->site_url,
	        );
            $id = $order->id;
            $total = $order->data['total'];
            $name = $order->data['billing']['first_name'] . ' ' . $order->data['billing']['last_name'];
            $reference = $order->data['order_key'];

            $paiement = new Payments($params);
            try {
                $idPaiement = $paiement->initPayment(
                    strval($id),
                    $total,
                    $name,
                    $reference,
                );
                if ($idPaiement == '') {
                    echo 'Erreur d identification de paiement.';
                    throw new Exception('');
                }
                WC()->session->set('order_id', $order->id);
            } catch (Exception $e) {
                throw new Exception($e);
            }

            $link = Config::$URL_PAIEMENT . $idPaiement;
            return [
                'result' => 'success',
                'redirect' => $link,
            ];
                        
        }

        /*
		 * WEBHOOK
		 */
		public function webhook() {

            $order = wc_get_order( $_GET['id'] );
	        $order->payment_complete();
	        $order->reduce_order_stock();

	        update_option('webhook_debug', $_GET);
                        
        }

    }

}

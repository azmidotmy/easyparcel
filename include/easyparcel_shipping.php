<?php
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
/**
 * Check if WooCommerce is active
 */

    if ( ! class_exists( 'WC_Easyparcel_Shipping_Method' ) ) {
      class WC_Easyparcel_Shipping_Method extends WC_Shipping_Method {
        /**
         * Constructor for your shipping class
         *
         * @access public
         * @return void
         */
        public function __construct() {
          $this->id                 = 'easyparcel'; // Id for your shipping method. Should be unique.
          $this->method_title       = __( 'EasyParcel Shipping ' );  // Title shown in admin
          $this->method_description = __( 'Allows buyer to choose for their favourite shipping method.' ); // Description shown in admin
          $this->title              = "EasyParcel Shipping"; // This can be added as an setting but for this example its forced.
          $this->init();
          $this->enabled = $this->settings['enabled'];
          
        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init() {
          // Load the settings API
          $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
          $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
          // Save settings in admin if you have any defined
          add_action( 'admin_notices', array( $this, 'easyparcel_admin_notice' ) );
          add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
          }


       /**
         * Notification when api key and secret is not set
         *
         * @access public
         * @return void
         */
        public function easyparcel_admin_notice() {
        
            if ( !class_exists( 'Easyparcel_Shipping_API' ) ){
                    // Include Easyparcel API
                    include_once 'easyparcel_api.php';
                }
            Easyparcel_Shipping_API::init();
            $auth=Easyparcel_Shipping_API::auth();
                if ($this->get_option('easyparcel_email') == '' || ($this->get_option('integration_id') == '') || ($this->get_option( "cust_rate" ) != 'fix_rate' && ($this->get_option('sender_postcode') == '')) ) 
                {
                    echo '<div class="error">Please go to <bold>WooCommerce > Settings > Shipping > Easyparcel Shipping</bold> to add your email,integration_id and sender code. </div>';
                }elseif($auth != 'Success.'){
                    echo '<div class="error">'.$auth.' Please go to <bold>WooCommerce > Settings > Shipping > Easyparcel Shipping</bold> enter your valid email,integration_id</div>';
                 }     
        }
        /**
         * Initialise Gateway Settings Form Fields
         */
        //loading $this->init_form_fields();
            function init_form_fields() {
             $this->form_fields = array(
                'enabled' => array(
                          'title' => __( 'Enable', 'easyparcel' ),
                          'type' => 'checkbox',
                          'description' => __( 'Enable EasyParcel Shipping', 'easyparcel' ),
                          'default' => 'yes'
                ),
                'easyparcel_email' => array(
                    'title'             => __( '<font color="red">*</font>EasyParcel Login Email', 'easyparcel' ),
                    'type'              => 'text',
                    'description'       => __( 'Enter your registered EasyParcel email here. If you do not have an account yet, sign up for a free account at www.easyparcel.com', 'easyparcel' ),
                    'desc_tip'          => true,
                    'default'           => '',
                    'required'          => true
                ),'integration_id' => array(
                    'title'             => __( '<font color="red">*</font>Integration ID', 'easyparcel' ),
                    'type'              => 'text',
                    'description'       => __( 'Here’s how to get integration ID:<br/>
                                              1. Login your EasyParcel Account<br/>
                                              2. Click " Dashboard" - "Integrations" - "Add New Store" <br/>
                                              3. Choose " WooCommerce" <br/>
                                              4. Fill in required details <br/>
                                              5. Copy the Integration ID and paste it here.', 'easyparcel' ),
                    'desc_tip'          => true,
                    'required'          => true
                ),'sender_postcode' => array(
                    'title'             => __( '<font color="red">*</font>Sender Postcode', 'easyparcel' ),
                    'type'              => 'text',
                    'required'          => true
                ),'cust_rate'           => array(
                    'title'             => __( 'Display Shipping Rate', 'easyparcel' ),
                    'type'              => 'select',
                    'description'       => __( "You may display different types of shipping rates on your checkout page:<br/><br/>
                                              1) Fixed rate: A fixed amount based on product weight.<br/>
                                              2) EasyParcel Member/Promo Rate: The rate you're enjoying right now. Eg: RM6 from 1kg<br/>
                                              3) EasyParcel Public Rate: Non-member rate. For eg: RM10.30 from 1 kg", 'easyparcel' ),
                    'desc_tip'          => true,
                    'default'           => 'normal', 
                    'options'           => array( 'fix_rate'=>'Fixed Rate','member_rate'=>'EasyParcel Member Rate','normal_rate'=>'EasyParcel Public Rate'),
                ),'fix_rate'           => array(
                    'title'             => __( 'Fixed Rate (RM)', 'easyparcel' ),
                    'type'              => 'text',
                    'description'       => __( 'Shipping rate (RM) for first 1KG', 'easyparcel' ),
                    'desc_tip'          => false,
                    'default'           => '', 
                    'placeholder'       => 'RM 0.00'
                ),'fix_rate_above_1kg'  => array(
                    'type'              => 'text',
                    'description'       => __( 'Shipping rate (RM) for every additional KG', 'easyparcel' ),
                    'desc_tip'          => false,
                    'default'           => '', 
                    'placeholder'       => 'RM 0.00'
                ),'courier_option'  => array(
                    'title'             => __( 'Display Courier Option', 'easyparcel' ),
                    'type'              => 'select',
                    'default'           => 'cheaper',
                    'options'           => array( 
                      'cheaper'=>'Cheapest Courier',
                      'EP-CR0A'=>'Poslaju National Courier',
                      'EP-CR0M'=>'Nationwide Express Courier Service Berhad',
                      'EP-CR0O' => 'Pgeon Delivery',
                      'EP-CR0Z' => 'CJ Century',
                      'EP-CR0C' => 'DHL eCommerce',
                      'EP-CR03' => 'Aramex',
                      'EP-CR0W' => 'SnT Global',
                      'EP-CR0J' => 'Ultimate Consolidators',
                      'EP-CR0D' => 'Airpak',
                      'all'=>'All')
                )
                
              
             );
        } // End init_form_fields()



        /**
         * calculate_shipping function.
         *
         * @access public
         * @param mixed $package
         * @return void
         */
        public function calculate_shipping( $package=array() ) {

            $destination = $package["destination"];

            $items = array();

            $product_factory = new WC_Product_Factory();
              foreach ( $package["contents"] as $key => $item ) {

                // default product - assume it is simple product
                $product = $product_factory->get_product( $item["product_id"] );
                $product_data = $product_factory->get_product( $item["data"] );
                $product_status=$item["data"]->get_type();
                // if this item is variation, get variation product instead
                if ($product_status == "variation" ) {
                  $product = $product_factory->get_product( $item["variation_id"] );
                }
                       
                for ( $i=0; $i < $item["quantity"]; $i++ ) {

                      $items[] = array(
                         "weight"           =>  $this->weightToKg( $product->get_weight() ),
                         "height"                  =>  $this->defaultDimension( $this->dimensionToCm( $product->get_height() ) ),
                         "width"                   =>  $this->defaultDimension( $this->dimensionToCm( $product->get_width() ) ),
                         "length"                  =>  $this->defaultDimension( $this->dimensionToCm( $product->get_length() ) )
                      );
                }
            }
          if ( !class_exists( 'Easyparcel_Shipping_API' ) ){
                    // Include Easyparcel API
                    include_once 'easyparcel_api.php';
                }

         try {
              Easyparcel_Shipping_API::init();
              $auth=Easyparcel_Shipping_API::auth();
               if($auth != 'Success.')
                {
                     wc_add_notice( $auth);
                }else{
                     $i=0;
                    $weight=0;
                    foreach ($items as $item) {
                   
                            $weight .= $items[$i]['weight'];
                            $i++;
                        
                    }
                    

                $WC_Country = new WC_Countries();
                 
                  $rates = Easyparcel_Shipping_API::getShippingRate($destination, $items,$weight);
                  
                $weight=ceil($weight);
                
                  if($this->get_option( "courier_option" ) == 'cheaper')
                  {
                    $rates = $this->get_cheaper_rate($rates);
                  }

                 foreach ( $rates as $rate ) {
                        $courier_label = $rate->Courier_Name." ";

                        $shipping_rate = array(
                          'id'      =>  $rate->Service_ID,
                          'label'   =>  $courier_label,
                          'cost'    =>  $rate->Price
                        );

                        if($rate->Service_Type == 'parcel' && $this->get_option( "courier_option" ) == 'all')
                        {
                            if($this->get_option("cust_rate") == 'fix_rate')
                            {
                                if($this->settings['fix_rate'] != '' || $this->settings['fix_rate_above_1kg'] != '')
                                {
                                    if($weight <= 1 )
                                    {

                                        $shipping_rate['cost'] = $this->settings['fix_rate'];
                                    }elseif($weight > 1 ){
                                        $shipping_rate['cost'] = $this->settings['fix_rate_above_1kg'];

                                        $fkg = $this->settings['fix_rate'];
                                        $mkg = $this->settings['fix_rate_above_1kg'];
                                        $mweight = $weight - 1 ;
                                        $mPrice= $mkg * $mweight;
                                        $shipping_rate['cost'] = $fkg + $mPrice;
                                    }
                                }
                            }
                                $this->add_rate( $shipping_rate );

                        }
                      if($rate->Service_Type == 'parcel')
                      {
                        if($this->get_option( "courier_option" ) != 'cheaper')
                        {
                            if($rate->Courier_ID == $this->get_option("courier_option"))
                            {
                                if($this->get_option( "cust_rate" ) == 'fix_rate')
                               { 
                                if($this->settings['fix_rate'] != '' || $this->settings['fix_rate_above_1kg'] != '')
                                {
                                    if($weight <= 1 )
                                    {
                                        $shipping_rate['cost'] = $this->settings['fix_rate'];
                                    }elseif($weight > 1 ){
                                        $shipping_rate['cost'] = $this->settings['fix_rate_above_1kg'];

                                         $fkg = $this->settings['fix_rate'];
                                        $mkg = $this->settings['fix_rate_above_1kg'];
                                         $mweight = $weight - 1 ;
                                        $mPrice= $mkg * $mweight;
                                        $shipping_rate['cost'] = $fkg + $mPrice;
                                    }
                                }
                               }
                                // Register the rate
                                $this->add_rate( $shipping_rate );
                            }
                        }elseif($this->get_option( "courier_option" ) == 'cheaper')
                        {
                            
                                if($this->get_option( "cust_rate" ) == 'fix_rate')
                               { 
                                if($this->settings['fix_rate'] != '' || $this->settings['fix_rate_above_1kg'] != '')
                                {
                                    if($weight <= 1 )
                                    {
                                        $shipping_rate['cost'] = $this->settings['fix_rate'];
                                    }elseif($weight > 1 ){
                                        $shipping_rate['cost'] = $this->settings['fix_rate_above_1kg'];

                                         $fkg = $this->settings['fix_rate'];
                                        $mkg = $this->settings['fix_rate_above_1kg'];
                                         $mweight = $weight - 1 ;
                                        $mPrice= $mkg * $mweight;
                                        $shipping_rate['cost'] = $fkg + $mPrice;
                                    }
                                }
                               }
                                // Register the rate
                                $this->add_rate( $shipping_rate );
                            
                        }else
                        {
                                $this->add_rate( $shipping_rate );
                        }
                     }
                   }
             }

            }
            catch( Exception $e ) {
                    $message = sprintf( __( 'Easyparcel Shipping Method is not set properly! Error: %s', 'easyparcel' ),$e->getMessage() );

                    $messageType = "error";
                    wc_add_notice( $message, $messageType );

            }   
        
      }

        /**
        * This function is convert dimension to cm
        *
        * @access protected
        * @param number
        * @return number
        */
        protected function dimensionToCm( $length ) {
            $dimension_unit = get_option('woocommerce_dimension_unit');
            // convert other units into cm
            // $length = double($length);
            if ( $dimension_unit != 'cm' ) {
                if ( $dimension_unit == 'm' ) {
                    return $length * 100;
                }
                else if ( $dimension_unit == 'mm' ) {
                    return $length * 0.1;
                }
                else if ( $dimension_unit == 'in' ) {
                    return $length * 2.54;
                }
                 else if ( $dimension_unit == 'yd' ) {
                    return $length * 91.44;
                }
            }

            // already in cm
            return $length;
        }

        /**
         * This function is convert weight to kg
         *
         * @access protected
         * @param number
         * @return number
         */
        protected function weightToKg( $weight ) {
             $weight_unit = get_option( 'woocommerce_weight_unit' );
             // convert other unit into kg
             // $weight = double($weight);
               if ( $weight_unit != 'kg' ) {
                    if ( $weight_unit == 'g')  {
                        return $weight * 0.001;
                    }
                    else if ( $weight_unit == 'lbs' ) {
                        return $weight * 0.453592;
                    }
                    else if ( $weight_unit == 'oz' ) {
                        return $weight * 0.0283495;
                    }
               }

               // already kg
               return $weight;
        }


        /**
        * This function return default value for length
        *
        * @access protected
        * @param number
        * @return number
        */
        protected function defaultDimension( $length ) {
             // default dimension to 1 if it is 0
            // $length = double($length);
            return $length > 0 ? $length : 0.1;
        }

        /**** Price Display*************************/
       /**
         * This function is found the cheapeast Courier from EasyParcel
         *
         * @access protected
         * @param array
         * @return array
         */
         protected function get_cheaper_rate($rates) {
            $prefer_rates = array();
            $lowest = 0;
            $index = 0;
            if ( empty( $rates ) ) {
                return $prefer_rates;
            }
            foreach ( $rates as $rate ) {
              if($rates[$index]->Service_Type == 'parcel'){
                 $nowRate=$rates[$index]->Price;
                 $bef4Rate=$rates[$lowest]->Price;

                if ($nowRate == $bef4Rate ) {
                     $lowest = $index;
                     $prefer_rates[$rates[$lowest]->Courier_ID] = $rates;
                }elseif($nowRate < $bef4Rate){
                      $prefer_rates = array();
                      $lowest = $index;
                     $prefer_rates[$rates[$lowest]->Courier_ID] = $rates;
                }
              }
                $index++;
             $prefer_rates[$rates[$lowest]->Courier_ID] = $rates[$lowest];;
           }         
            return $prefer_rates;
        }

        
      }

    }
  

  





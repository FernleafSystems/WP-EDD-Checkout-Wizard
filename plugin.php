<?php
/**
 * Plugin Name:     FS EDD Checkout Wizard
 * Plugin URI:      https://github.com/FernleafSystems/WP-EDD-Checkout-Wizard
 * Description:     Adds a form wizard with validation to your checkout page
 * Version:         0.0.1
 * Author:          FernleafSystems
 * Author URI:      https://onedollarplugin.com
 * Text Domain:     icwp-edd-checkout-wizard
 */

/**
 * Copyright (c) 2018 FernleafSystems <support@onedollarplugin.com>
 * All rights reserved.
 *
 * "FS EDD Checkout Wizard" is distributed under the GNU
 * General Public License, Version 2, June 1991. Copyright (C) 1989, 1991 Free
 * Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * Credit to original plugin author: https://wordpress.org/plugins/edd-checkout-wizard/
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Checkout_Wizard' ) ) {

    /**
     * Main EDD_Checkout_Wizard class
     *
     * @since       1.0.0
     */
    class EDD_Checkout_Wizard {

        /**
         * @var         EDD_Checkout_Wizard $instance The one true EDD_Checkout_Wizard
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      EDD_Checkout_Wizard self::$instance The one true EDD_Checkout_Wizard
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Checkout_Wizard();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_CHECKOUT_WIZARD_VER', '1.0.0' );

            // Plugin path
            define( 'EDD_CHECKOUT_WIZARD_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_CHECKOUT_WIZARD_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            // Include scripts
            require_once EDD_CHECKOUT_WIZARD_DIR . 'includes/scripts.php';
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            add_action( 'edd_before_checkout_cart', array( $this, 'render_html_tabs' ) );
            add_action( 'edd_after_purchase_form', array( $this, 'render_html_buttons' ) );
            // Register settings
            add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_CHECKOUT_WIZARD_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_checkout_wizard_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-checkout-wizard' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-checkout-wizard', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-checkout-wizard/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-checkout-wizard/ folder
                load_textdomain( 'edd-checkout-wizard', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-checkout-wizard/languages/ folder
                load_textdomain( 'edd-checkout-wizard', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-checkout-wizard', false, $lang_dir );
            }
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing EDD settings array
         * @return      array The modified EDD settings array
         */
        public function settings( $settings ) {
            $new_settings = array(
                array(
                    'id'    => 'edd_checkout_wizard_settings',
                    'name'  => '<strong>' . __( 'Plugin Name Settings', 'edd-checkout-wizard' ) . '</strong>',
                    'desc'  => __( 'Configure Plugin Name Settings', 'edd-checkout-wizard' ),
                    'type'  => 'header',
                )
            );

            return array_merge( $settings, $new_settings );
        }

        public function render_html_tabs() {
            if( isset($_POST['action']) && $_POST['action'] == 'edd_recalculate_taxes' ) {
                return;
            }

            $tabs = array(
                'overview' => array(
                    'label' => __( 'Overview' ),
                    'selectors' => array(
                        '#edd_checkout_cart_form',
                        '#edd-rp-checkout-wrapper', // Support for EDD Recommended Products
                    )
                ),
                'payment_method' => array(
                    'label' => __( 'Payment Method' ),
                    'selectors' => array(
                        '.edd-payment-icons',
                        '#edd_payment_mode_select',
                        '#edd_cc_fields',
                    )
                ),
                'account' => array(
                    'label' => __( 'Account' ),
                    'selectors' => array(
                        '#edd_checkout_login_register',
                        '#edd_checkout_user_info',
                    )
                ),
                'address' => array(
                    'label' => __( 'Billing Address' ),
                    'selectors' => array(
                        '#edd_cc_address',
                        '#edd_vat_info_show', // Support for EDD VAT
                    )
                ),
                'payment' => array(
                    'label' => __( 'Payment' ),
                    'selectors' => array(
                        '#edd_purchase_submit',
                    )
                ),
            );

            if( edd_get_cart_total() <= 0 ) {
                unset($tabs['payment_method']);
            }

            $tabs = apply_filters( 'edd_checkout_wizard_checkout_tabs', $tabs );

            $current_tab = 'tab-' . array_keys( $tabs )[0];
            $current_step = 1;

            ?>
            <div class="edd-checkout-wizard-nav-tabs">
            <?php
                foreach($tabs as $tab_id => $tab_args) {
                    ?>
                    <a id="tab-<?php echo $tab_id; ?>"
                       href="#"
                       class="edd-checkout-wizard-nav-tab nav-tab <?php echo ($current_tab == 'tab-' . $tab_id) ? 'nav-tab-active' : ''; ?>"
                       data-selector="<?php echo implode( ', ', $tab_args['selectors'] ); ?>"
                       data-validated="false"
                       data-current="<?php echo ($current_tab == 'tab-' . $tab_id) ? 'true' : 'false'; ?>"
                    >
                        <span class="edd-checkout-wizard-nav-tab-number"><?php echo $current_step; ?></span>
                        <span class="edd-checkout-wizard-nav-tab-label"><?php echo $tab_args['label']; ?></span>
                    </a>
                    <?php

                    $current_step++;
                }
            ?>
            </div>
            <?php
        }

        public function render_html_buttons() {
            $args = array(
                'style'       => edd_get_option( 'button_style', 'button' ),
                'color'       => edd_get_option( 'checkout_color', 'blue' ),
            );

            $style = edd_get_option( 'button_style', 'button' );
            $color = edd_get_option( 'checkout_color', 'blue' );

            $color = ( $color == 'inherit' ) ? '' : $color;
            ?>
            <div class="edd-checkout-wizard-buttons">
                <button
                    type="button"
                    id="edd-checkout-wizard-prev-button"
                    class="edd-checkout-wizard-button <?php echo $style; ?> <?php echo $color; ?>"
                    aria-hidden="true"
                >
                    <span class="edd-checkout-wizard-button-label"><?php echo __( 'Previous' ); ?></span>
                </button>
                <button
                    type="button"
                    id="edd-checkout-wizard-next-button"
                    class="edd-checkout-wizard-button <?php echo $style; ?> <?php echo $color; ?>"
                    aria-hidden="true"
                >
                    <span class="edd-checkout-wizard-button-label"><?php echo __( 'Next' ); ?></span>
                </button>
            </div>
            <?php
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_Checkout_Wizard
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_Checkout_Wizard The one true EDD_Checkout_Wizard
 */
function edd_checkout_wizard() {
    return EDD_Checkout_Wizard::instance();
}
add_action( 'plugins_loaded', 'edd_checkout_wizard' );


/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */
function edd_checkout_wizard_activation() {
    /* Activation functions here */
}
register_activation_hook( __FILE__, 'edd_checkout_wizard_activation' );

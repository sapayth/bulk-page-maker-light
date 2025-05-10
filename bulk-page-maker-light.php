<?php
/*
Plugin Name: Bulk Page Maker Light
Description: A Light plugin to generate bulk WordPress Pages or Posts
Version: 2.0.0
Author: Sapayth H.
Author URI: http://sapayth.com
License: GPLv2 or later
Requires at least: 5.6
Requires PHP: 5.6
Text Domain: bpm-light
*/

/*
 * **********************************************************************
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
*/

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
* The main plugin class
*/
final class Bulk_Page_Maker {

    const VERSION = '2.0.0';

    /*
    * class constructor
    */
    private function __construct() {
        $this->define_constants();

        register_activation_hook( __FILE__, [ $this, 'activate'] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate'] );

        add_action( 'init', [$this, 'init_plugin'] );
    }

    /*
    * initializes a singleton instance
    */
    public static function init() {
        static $instance = false;

        if( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * constants
     */
    public function define_constants() {
        $this->define( 'BPM_VERSION', self::VERSION );
        $this->define( 'BPM_FILE', __FILE__ );
        $this->define( 'BPM_PATH', __DIR__ );
        $this->define( 'BPM_URL', plugins_url('', BPM_FILE) );
        $this->define( 'BPM_ASSETS', BPM_URL . '/assets' );
    }

    /**
     * Define constant if not already set.
     *
     * @param string    $name  Constant name.
     * @param mixed     $value Constant value.
     */
    private function define( $const, $value ) {
        if( ! defined( $const ) ) {
            define( $const, $value );
        }
    }

    /**
     * initialize the plugin
     * @return void
     */
    public function init_plugin() {
        new Bulk\Page\Maker\Assets();

        new Bulk\Page\Maker\Api();

        if( is_admin() ) {
            new Bulk\Page\Maker\Admin();
        }
    }

    /**
     * Do stuff upon plugin activation
     */
    public function activate() {
        $installer = new Bulk\Page\Maker\Installer();
        $installer->run();
    }

    /**
     * Do stuff upon plugin deactivation
     */
    public function deactivate() {
        $transient_posts = 'bpmaker_all_pages';
        $transient_count = 'bpmaker_pages_count';

        // delete releated transient
        if ( false !== get_transient( $transient_count ) ) {
            delete_transient( $transient_count );
        }

        // delete releated transient
        if ( false !== get_transient( $transient_posts ) ) {
            delete_transient( $transient_posts );
        }
    }
}

/**
* initialize the main plugin
*/
if ( ! function_exists( 'bpmaker_make_page' ) ) {
    function bpmaker_make_page() {
        return Bulk_Page_Maker::init();
    }
}

// start the plugin
bpmaker_make_page();

<?php

namespace Hametuha\Pattern;

/**
 * Table Screen UI
 */
abstract class TableScreen extends Singleton {

	protected $table_class = '';

	protected $parent = '';

	protected $slug = '';

	protected $icon = '';

	protected $position = 50;

	protected $has_search = true;

	/**
	 * @var \WP_List_Table
	 */
	protected $table = null;

	/**
	 * Capability to access this page.
	 *
	 * @return string
	 */
	protected function get_capability() {
		return 'manage_options';
	}

	/**
	 * Get page title.
	 *
	 * @return string
	 */
	abstract protected function get_title();

	/**
	 * Menu title
	 *
	 * @return string
	 */
	protected function get_menu_title() {
		return $this->get_title();
	}

	/**
	 * Executed inside constructor.
	 */
	protected function init() {
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_script' ] );
	}

	/**
	 * Do something on admin init.
	 */
	public function admin_init() {
		// Do nothing.
	}

	/**
	 * Override this function to enqueue assets.
	 *
	 * @param string $page
	 */
	public function admin_enqueue_script( $page ) {
	}

	/**
	 * Register page
	 */
	public function admin_menu() {
		if ( ! class_exists( $this->table_class ) ) {
			trigger_error( sprintf( 'Table class %s doesn\'t exist.', $this->table_class ), E_USER_WARNING );
			return;
		}
		if ( $this->parent ) {
			add_submenu_page( $this->parent, $this->get_title(), $this->get_menu_title(), $this->get_capability(), $this->slug, [ $this, 'render' ] );
		} else {
			add_menu_page( $this->get_title(), $this->get_menu_title(), $this->get_capability(), $this->slug, [ $this, 'render' ], $this->icon, $this->position );
		}
	}

	/**
	 * Render screen.
	 */
	public function render() {
		$action      = basename( $_SERVER['SCRIPT_FILENAME'] );
		$this->table = new $this->table_class();
		$this->table->prepare_items();
		?>
		<div class="wrap">
			<h2><?php echo esc_html( $this->get_title() ); ?></h2>
			<?php $this->before_table(); ?>
			<form action="<?php echo esc_url( admin_url( $action ) ); ?>" method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( filter_input( INPUT_GET, 'page' ) ); ?>" />
			<?php
			if ( $this->has_search ) {
				$this->table->search_box( __( 'Search' ), 's' );
			}
				ob_start();
				$this->table->display();
				$content = ob_get_contents();
				ob_end_clean();
				// Remove http referer
				$content = preg_replace( '#<input type="hidden" name="_wp_http_referer"[^>]+>#u', '', $content );
				echo $content;
			?>
			</form>
			<?php $this->after_table(); ?>
		</div>
		<?php
	}

	/**
	 * Do something before table.
	 */
	protected function before_table() {
		do_action( 'sharee_before_table', $this->table_class );
	}

	/**
	 * Do something after table.
	 */
	protected function after_table() {
		do_action( 'sharee_after_table', $this->table_class );
	}

}

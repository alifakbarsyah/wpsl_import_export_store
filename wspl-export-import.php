<?php

/**
 * Plugin Name: WPSL Import/Export Extension
 * Description: Import and Export functionality for WP Store Locator (stores and settings)
 * Version: 2.0
 * Author: GLPZ
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

class WPSL_Import_Export {
	public function __construct() {
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'handle_import_export'));
	}

	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=wpsl_stores',
			'WPSL Import/Export Extention',
			'Import/Export Extention',
			'manage_options',
			'wpsl-import-export',
			array($this, 'render_admin_page')
		);
	}

	public function render_admin_page() {
?>
		<div class="wrap">
			<h1>WPSL Import/Export</h1>

			<!-- Store Export Section -->
			<div class="card">
				<h2>Export Stores</h2>
				<p>Download your store locations as a CSV file.</p>
				<form method="post" action="">
					<?php wp_nonce_field('wpsl_export_stores_nonce', 'wpsl_export_stores_nonce'); ?>
					<input type="hidden" name="action" value="export_stores">
					<p><input type="submit" class="button button-primary" value="Export Stores to CSV"></p>
				</form>
			</div>

			<!-- Store Import Section -->
			<div class="card" style="margin-top: 20px;">
				<h2>Import Stores</h2>
				<p>Import store locations from a CSV file.</p>
				<div class="csv-template" style="margin-bottom: 10px;">
					<a type="button" class="button" href="<?php echo plugin_dir_url(__FILE__) . 'import-WPSL-data-template.csv'; ?>" download>
						Download CSV Template
					</a>
				</div>
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field('wpsl_import_stores_nonce', 'wpsl_import_stores_nonce'); ?>
					<input type="hidden" name="action" value="import_stores">
					<p><input type="file" name="import_file" accept=".csv" required></p>
					<p><input type="submit" class="button button-primary" value="Import Stores from CSV"></p>
				</form>
			</div>

			<!-- Settings Export Section -->
			<div class="card" style="margin-top: 20px;">
				<h2>Export Settings</h2>
				<p>Download your current WPSL settings as a JSON file.</p>
				<form method="post" action="">
					<?php wp_nonce_field('wpsl_export_settings_nonce', 'wpsl_export_settings_nonce'); ?>
					<input type="hidden" name="action" value="export_settings">
					<p><input type="submit" class="button button-primary" value="Export Settings"></p>
				</form>
			</div>

			<!-- Settings Import Section -->
			<div class="card" style="margin-top: 20px;">
				<h2>Import Settings</h2>
				<p>Import WPSL settings from a JSON file.</p>
				<form method="post" action="" enctype="multipart/form-data">
					<?php wp_nonce_field('wpsl_import_settings_nonce', 'wpsl_import_settings_nonce'); ?>
					<input type="hidden" name="action" value="import_settings">
					<p><input type="file" name="import_file" accept=".json" required></p>
					<p><input type="submit" class="button button-primary" value="Import Settings"></p>
				</form>
			</div>
		</div>
		<?php
	}

	public function handle_import_export() {
		if (!isset($_POST['action'])) {
			return;
		}

		switch ($_POST['action']) {
			case 'export_stores':
				$this->handle_store_export();
				break;
			case 'import_stores':
				$this->handle_store_import();
				break;
			case 'export_settings':
				$this->handle_settings_export();
				break;
			case 'import_settings':
				$this->handle_settings_import();
				break;
		}
	}

	private function handle_store_export() {
		if (!wp_verify_nonce($_POST['wpsl_export_stores_nonce'], 'wpsl_export_stores_nonce')) {
			wp_die('Security check failed');
		}

		$stores = get_posts(array(
			'post_type' => 'wpsl_stores',
			'posts_per_page' => -1,
		));

		$csv_lines = array();
		$csv_lines[] = array(
			'Store Name',
			'Address',
			'Address2',
			'City',
			'State',
			'Zip',
			'Country',
			'Latitude',
			'Longitude',
			'Phone',
			'Fax',
			'Email',
			'Website'
		);

		foreach ($stores as $store) {
			$meta = get_post_meta($store->ID);
			$csv_lines[] = array(
				$store->post_title,
				isset($meta['wpsl_address'][0]) ? $meta['wpsl_address'][0] : '',
				isset($meta['wpsl_address2'][0]) ? $meta['wpsl_address2'][0] : '',
				isset($meta['wpsl_city'][0]) ? $meta['wpsl_city'][0] : '',
				isset($meta['wpsl_state'][0]) ? $meta['wpsl_state'][0] : '',
				isset($meta['wpsl_zip'][0]) ? $meta['wpsl_zip'][0] : '',
				isset($meta['wpsl_country'][0]) ? $meta['wpsl_country'][0] : '',
				isset($meta['wpsl_lat'][0]) ? $meta['wpsl_lat'][0] : '',
				isset($meta['wpsl_lng'][0]) ? $meta['wpsl_lng'][0] : '',
				isset($meta['wpsl_phone'][0]) ? $meta['wpsl_phone'][0] : '',
				isset($meta['wpsl_fax'][0]) ? $meta['wpsl_fax'][0] : '',
				isset($meta['wpsl_email'][0]) ? $meta['wpsl_email'][0] : '',
				isset($meta['wpsl_url'][0]) ? $meta['wpsl_url'][0] : ''
			);
		}

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="wpsl-stores-' . date('Y-m-d') . '.csv"');

		$fp = fopen('php://output', 'wb');
		foreach ($csv_lines as $line) {
			fputcsv($fp, $line);
		}
		fclose($fp);
		exit();
	}

	private function handle_store_import() {
		if (!wp_verify_nonce($_POST['wpsl_import_stores_nonce'], 'wpsl_import_stores_nonce')) {
			wp_die('Security check failed');
		}

		if (!isset($_FILES['import_file'])) {
			wp_die('No file uploaded');
		}

		$file = $_FILES['import_file'];
		if ($file['error'] !== UPLOAD_ERR_OK) {
			wp_die('File upload error');
		}

		$handle = fopen($file['tmp_name'], 'r');
		$header = fgetcsv($handle); // Skip header row

		while (($data = fgetcsv($handle)) !== false) {
			$store_data = array(
				'post_title'   => $data[0],
				'post_type'    => 'wpsl_stores',
				'post_status'  => 'publish'
			);

			$store_id = wp_insert_post($store_data);

			if (!is_wp_error($store_id)) {
				update_post_meta($store_id, 'wpsl_address', $data[1]);
				update_post_meta($store_id, 'wpsl_address2', $data[2]);
				update_post_meta($store_id, 'wpsl_city', $data[3]);
				update_post_meta($store_id, 'wpsl_state', $data[4]);
				update_post_meta($store_id, 'wpsl_zip', $data[5]);
				update_post_meta($store_id, 'wpsl_country', $data[6]);
				update_post_meta($store_id, 'wpsl_lat', $data[7]);
				update_post_meta($store_id, 'wpsl_lng', $data[8]);
				update_post_meta($store_id, 'wpsl_phone', $data[9]);
				update_post_meta($store_id, 'wpsl_fax', $data[10]);
				update_post_meta($store_id, 'wpsl_email', $data[11]);
				update_post_meta($store_id, 'wpsl_url', $data[12]);
			}
		}

		fclose($handle);
		wp_redirect(add_query_arg('imported', 'stores', admin_url('edit.php?post_type=wpsl_stores&page=wpsl-import-export')));
		exit;
	}

	private function handle_settings_export() {
		if (!wp_verify_nonce($_POST['wpsl_export_settings_nonce'], 'wpsl_export_settings_nonce')) {
			wp_die('Security check failed');
		}

		$settings = array(
			'wpsl_settings' => get_option('wpsl_settings'),
			'wpsl_map_settings' => get_option('wpsl_map_settings'),
			'wpsl_search_settings' => get_option('wpsl_search_settings'),
			'wpsl_label_settings' => get_option('wpsl_label_settings'),
			'wpsl_template_settings' => get_option('wpsl_template_settings')
		);

		$filename = 'wpsl-settings-' . date('Y-m-d') . '.json';

		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . strlen(json_encode($settings)));
		echo json_encode($settings);
		exit;
	}

	private function handle_settings_import() {
		if (!wp_verify_nonce($_POST['wpsl_import_settings_nonce'], 'wpsl_import_settings_nonce')) {
			wp_die('Security check failed');
		}

		if (!isset($_FILES['import_file'])) {
			wp_die('No file uploaded');
		}

		$file_content = file_get_contents($_FILES['import_file']['tmp_name']);
		$settings = json_decode($file_content, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			add_settings_error(
				'wpsl_import_export',
				'invalid_json',
				'Error: Invalid JSON file',
				'error'
			);
			return;
		}

		foreach ($settings as $option_name => $option_value) {
			if (in_array($option_name, array(
				'wpsl_settings',
				'wpsl_map_settings',
				'wpsl_search_settings',
				'wpsl_label_settings',
				'wpsl_template_settings'
			))) {
				update_option($option_name, $option_value);
			}
		}

		wp_redirect(add_query_arg('imported', 'settings', admin_url('edit.php?post_type=wpsl_stores&page=wpsl-import-export')));
		exit;
	}

	public function add_admin_notices() {
		if (isset($_GET['imported'])) {
			$type = $_GET['imported'];
			$message = $type === 'stores' ? 'Stores have been successfully imported!' : 'Settings have been successfully imported!';
		?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html($message); ?></p>
			</div>
<?php
		}
	}
}

// Initialize the plugin
$wpsl_import_export = new WPSL_Import_Export();
add_action('admin_notices', array($wpsl_import_export, 'add_admin_notices'));

<?php
/*
Plugin Name: WPSL Import Export Extension
Description: Import and Export functionality for WP Store Locator
Version: 1.0
Author: Glpz
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit;
}

// Add menu item under WPSL menu
add_action('admin_menu', 'wpsl_import_export_menu');

function wpsl_import_export_menu() {
	add_submenu_page(
		'edit.php?post_type=wpsl_stores',
		'Import/Export Stores',
		'Import/Export',
		'manage_options',
		'wpsl-import-export',
		'wpsl_import_export_page'
	);
}

// Create the import/export page
function wpsl_import_export_page() {
?>
	<div class="wrap">
		<h1>WPSL Import/Export</h1>

		<!-- Export Section -->
		<div class="card">
			<h2>Export Stores</h2>
			<form method="post" action="">
				<?php wp_nonce_field('wpsl_export_nonce', 'wpsl_export_nonce'); ?>
				<input type="hidden" name="wpsl_action" value="export">
				<p class="submit">
					<input type="submit" name="submit" class="button button-primary" value="Export to CSV">
				</p>
			</form>
		</div>

		<!-- Import Section -->
		<div class="card">
			<h2>Import Stores</h2>
			<div class='csv-template'>
				<a type='button' class='button button-success' href="<?php echo get_site_url(); ?>/wp-content/plugins/wpsl-export-import/import-WPSL-data-template.csv" download>
					CSV Template Notes
				</a>
			</div>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field('wpsl_import_nonce', 'wpsl_import_nonce'); ?>
				<input type="hidden" name="wpsl_action" value="import">
				<p>
					<input type="file" name="wpsl_import_file" accept=".csv">
				</p>
				<p class="submit">
					<input type="submit" name="submit" class="button button-primary" value="Import from CSV">
				</p>
			</form>
		</div>
	</div>
	<?php
}

// Handle export functionality
add_action('admin_init', 'handle_wpsl_export');

function handle_wpsl_export() {
	if (!isset($_POST['wpsl_action']) || $_POST['wpsl_action'] !== 'export') {
		return;
	}

	if (!wp_verify_nonce($_POST['wpsl_export_nonce'], 'wpsl_export_nonce')) {
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
	header('Content-Disposition: attachment; filename="wpsl-stores-export-' . date('Y-m-d') . '.csv"');

	$fp = fopen('php://output', 'wb');
	foreach ($csv_lines as $line) {
		fputcsv($fp, $line);
	}
	fclose($fp);
	exit();
}

// Handle import functionality
add_action('admin_init', 'handle_wpsl_import');

function handle_wpsl_import() {
	if (!isset($_POST['wpsl_action']) || $_POST['wpsl_action'] !== 'import') {
		return;
	}

	if (!wp_verify_nonce($_POST['wpsl_import_nonce'], 'wpsl_import_nonce')) {
		wp_die('Security check failed');
	}

	if (!isset($_FILES['wpsl_import_file'])) {
		wp_die('No file uploaded');
	}

	$file = $_FILES['wpsl_import_file'];
	if ($file['error'] !== UPLOAD_ERR_OK) {
		wp_die('File upload error');
	}

	$handle = fopen($file['tmp_name'], 'r');
	$header = fgetcsv($handle); // Skip header row

	while (($data = fgetcsv($handle)) !== false) {
		// Create new store post
		$store_data = array(
			'post_title'   => $data[0],
			'post_type'    => 'wpsl_stores',
			'post_status'  => 'publish'
		);

		$store_id = wp_insert_post($store_data);

		if (!is_wp_error($store_id)) {
			// Update store meta data
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

	// Redirect back to the import page with success message
	wp_redirect(add_query_arg('imported', 'success', admin_url('edit.php?post_type=wpsl_stores&page=wpsl-import-export')));
	exit;
}

// Add admin notice for successful import
add_action('admin_notices', 'wpsl_import_admin_notice');

function wpsl_import_admin_notice() {
	if (isset($_GET['imported']) && $_GET['imported'] === 'success') {
	?>
		<div class="notice notice-success is-dismissible">
			<p>Stores have been successfully imported!</p>
		</div>
<?php
	}
}

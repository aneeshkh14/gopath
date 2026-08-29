<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin data management (Export, Backup, Reset).
 */
class GEP_Admin_Data {

	/**
	 * Export all attempts to CSV.
	 */
	public function export_attempts() {
		global $wpdb;
		$sql = "SELECT 
					a.id, 
					u.user_email, 
					t.title as test_name, 
					a.score, 
					a.is_pass, 
					a.status, 
					a.start_time, 
					a.end_time 
				FROM {$wpdb->prefix}gep_attempts a
				JOIN {$wpdb->users} u ON a.user_id = u.ID
				JOIN {$wpdb->prefix}gep_tests t ON a.test_id = t.id
				ORDER BY a.id DESC";
		
		$results = $wpdb->get_results( $sql, 'ARRAY_A' );
		
		if ( empty( $results ) ) return;

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="gep-attempts-'.date('Y-m-d').'.csv"');

		$output = fopen('php://output', 'w');
		fputcsv( $output, array('Attempt ID', 'Student Email', 'Test Name', 'Score (%)', 'Pass Status', 'Attempt Status', 'Start Time', 'End Time') );

		foreach ( $results as $row ) {
			fputcsv( $output, array(
				$row['id'],
				$row['user_email'],
				$row['test_name'],
				$row['score'],
				$row['is_pass'] ? 'PASS' : 'FAIL',
				strtoupper($row['status']),
				$row['start_time'],
				$row['end_time']
			) );
		}
		fclose( $output );
		exit;
	}

	/**
	 * Reset all plugin data (Extreme caution).
	 */
	public function reset_all_data() {
		if ( ! current_user_can( 'manage_options' ) ) return false;

		global $wpdb;
		$tables = array(
			'gep_attempts',
			'gep_violations',
			'gep_orders',
			'gep_user_test_access'
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}$table" );
		}

		return true;
	}
}

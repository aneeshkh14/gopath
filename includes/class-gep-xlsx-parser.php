<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GEP_XLSX_Parser {
	public static function parse( $file_path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}

		$zip = new ZipArchive();
		if ( $zip->open( $file_path ) !== true ) {
			return false;
		}

		libxml_use_internal_errors(true);

		// 1. Read Shared Strings
		$shared_strings = array();
		$ss_data = $zip->getFromName('xl/sharedStrings.xml');
		if ( $ss_data ) {
			// Strip namespace prefixes and attributes for SimpleXML compatibility
			$ss_data = preg_replace( '/<(\/?[a-zA-Z0-9]+):/', '<$1', $ss_data );
			$ss_data = preg_replace( '/\s+[a-zA-Z0-9]+:[a-zA-Z0-9]+="[^"]*"/', '', $ss_data );
			$ss_data = str_replace( array( 'xmlns=', 'xmlns:' ), array( 'ns=', 'ns:' ), $ss_data );
			$xml = simplexml_load_string( $ss_data );
			if ( $xml && isset( $xml->si ) ) {
				foreach ( $xml->si as $val ) {
					if ( isset( $val->t ) ) {
						$shared_strings[] = (string) $val->t;
					} elseif ( isset( $val->r ) ) {
						$str = '';
						foreach ( $val->r as $r ) {
							if ( isset( $r->t ) ) $str .= (string) $r->t;
						}
						$shared_strings[] = $str;
					} else {
						$shared_strings[] = '';
					}
				}
			}
		}

		// 2. Read Sheet 1
		$sheet_data = $zip->getFromName('xl/worksheets/sheet1.xml');
		if ( ! $sheet_data ) {
			$zip->close();
			libxml_clear_errors();
			return false;
		}

		// Strip namespace prefixes and attributes for SimpleXML compatibility
		$sheet_data = preg_replace( '/<(\/?[a-zA-Z0-9]+):/', '<$1', $sheet_data );
		$sheet_data = preg_replace( '/\s+[a-zA-Z0-9]+:[a-zA-Z0-9]+="[^"]*"/', '', $sheet_data );
		$sheet_data = str_replace( array( 'xmlns=', 'xmlns:' ), array( 'ns=', 'ns:' ), $sheet_data );
		$xml = simplexml_load_string( $sheet_data );
		$rows = array();

		if ( $xml && isset( $xml->sheetData->row ) ) {
			foreach ( $xml->sheetData->row as $row ) {
				$current_row = array();
				$cell_index = 0;
				
				foreach ( $row->c as $c ) {
					// Handle missing empty cells based on 'r' attribute (e.g., A1, C1)
					$attr = $c->attributes();
					if ( isset( $attr['r'] ) ) {
						$col = preg_replace('/[0-9]/', '', (string)$attr['r']);
						$expected_index = self::col_to_index( $col );
						while ( $cell_index < $expected_index ) {
							$current_row[] = '';
							$cell_index++;
						}
					}

					$val = isset( $c->v ) ? (string) $c->v : '';
					$type = isset( $attr['t'] ) ? (string)$attr['t'] : '';

					if ( $type === 's' && $val !== '' ) {
						// Shared string
						$val = isset( $shared_strings[ (int) $val ] ) ? $shared_strings[ (int) $val ] : '';
					} elseif ( $type === 'inlineStr' && isset( $c->is->t ) ) {
						$val = (string) $c->is->t;
					}

					$current_row[] = $val;
					$cell_index++;
				}
				$rows[] = $current_row;
			}
		}

		$zip->close();
		libxml_clear_errors();
		return $rows;
	}

	private static function col_to_index( $col ) {
		$col = strtoupper( $col );
		$len = strlen( $col );
		$n = 0;
		for ( $i = 0; $i < $len; $i++ ) {
			$n = $n * 26 + ord( $col[$i] ) - 64;
		}
		return $n - 1;
	}
}

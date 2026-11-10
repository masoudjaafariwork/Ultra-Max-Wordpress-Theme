<?php
/**
 * Table widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\DataTable;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * A table such as a size chart or shipping rates. Write each row on a line
 * with cells separated by a vertical bar (|); the first line is the head.
 */
final class Table extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'table';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Table', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-table';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'data-table' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Table', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'caption',
					'type'    => 'text',
					'label'   => __( 'Table name', 'ultra-max-core' ),
					'default' => __( 'Size chart', 'ultra-max-core' ),
				),
				array(
					'id'      => 'show_caption',
					'type'    => 'switch',
					'label'   => __( 'Show the name above the table', 'ultra-max-core' ),
					'default' => true,
				),
				array(
					'id'      => 'data',
					'type'    => 'textarea',
					'label'   => __( 'Rows', 'ultra-max-core' ),
					'help'    => __( 'One row per line, cells separated by |. The first line is the head.', 'ultra-max-core' ),
					'rows'    => 8,
					'default' => __( "Size | Chest | Length\nS | 96 | 68\nM | 102 | 70\nL | 108 | 72", 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * Splits the rows text into cells.
	 *
	 * @param string $data Rows text.
	 * @return list<list<string>>
	 */
	public static function parse( string $data ): array {
		$rows  = array();
		$lines = preg_split( '/\r\n|\r|\n/', trim( $data ) );
		foreach ( is_array( $lines ) ? $lines : array() as $line ) {
			if ( '' !== trim( $line ) ) {
				$rows[] = array_map( 'trim', explode( '|', $line ) );
			}
		}
		return $rows;
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$rows = self::parse( $settings->string( 'data' ) );
		$head = array_shift( $rows ) ?? array();
		return DataTable::render(
			array(
				'caption'      => $settings->string( 'caption' ),
				'show_caption' => $settings->bool( 'show_caption' ),
				'head'         => $head,
				'rows'         => $rows,
			)
		);
	}
}

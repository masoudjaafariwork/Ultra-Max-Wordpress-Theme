<?php
/**
 * Picks the layout of each page location (BLD-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

/**
 * For a location such as the header, every device gets its own winner
 * among the layouts whose conditions match the request. Layouts that win
 * on several devices are printed once, hidden on the others; this is how a
 * store has one header for desktop and another for phones (HDR-04).
 *
 * @phpstan-import-type Entry from Layouts
 * @phpstan-import-type Devices from Layouts
 * @phpstan-type Pick array{id: int, type: string, devices: Devices, sticky: string}
 */
final class Resolver {

	public const DEVICES = array( 'desktop', 'tablet', 'mobile' );

	/**
	 * Picks per request and location.
	 *
	 * @var array<string, list<Pick>>
	 */
	private static array $cache = array();

	/**
	 * Layouts for a location on the current request.
	 *
	 * @param string $location Location: header, footer, single or archive.
	 * @return list<Pick>
	 */
	public static function location( string $location ): array {
		if ( ! isset( self::$cache[ $location ] ) ) {
			self::$cache[ $location ] = self::pick( Layouts::index(), $location, Context::current() );
		}
		return self::$cache[ $location ];
	}

	/**
	 * Forgets the picks, after settings or layouts change during a request.
	 */
	public static function reset(): void {
		self::$cache = array();
	}

	/**
	 * Picks the layouts of a location from a list, for a request.
	 *
	 * @param array   $entries  Published layouts.
	 * @param string  $location Location.
	 * @param Context $context  Request.
	 * @phpstan-param list<Entry> $entries
	 * @return list<Pick>
	 */
	public static function pick( array $entries, string $location, Context $context ): array {
		$types      = Types::ready();
		$candidates = array();
		foreach ( $entries as $entry ) {
			$type = $types[ $entry['type'] ] ?? null;
			if ( null === $type || $location !== $type['location'] ) {
				continue;
			}
			$score = Rules::score( $entry['conditions'], $context );
			if ( null !== $score ) {
				$candidates[] = array(
					'entry' => $entry,
					'score' => $score,
				);
			}
		}
		usort(
			$candidates,
			static fn ( array $a, array $b ): int => array( $b['entry']['conditions']['priority'], $b['score'], $b['entry']['modified'], $b['entry']['id'] )
				<=> array( $a['entry']['conditions']['priority'], $a['score'], $a['entry']['modified'], $a['entry']['id'] )
		);

		$picks = array();
		foreach ( self::DEVICES as $device ) {
			foreach ( $candidates as $candidate ) {
				$entry = $candidate['entry'];
				if ( ! $entry['conditions']['devices'][ $device ] ) {
					continue;
				}
				if ( ! isset( $picks[ $entry['id'] ] ) ) {
					$picks[ $entry['id'] ] = array(
						'id'      => $entry['id'],
						'type'    => $entry['type'],
						'devices' => array(
							'desktop' => false,
							'tablet'  => false,
							'mobile'  => false,
						),
						'sticky'  => $entry['conditions']['sticky'],
					);
				}
				$picks[ $entry['id'] ]['devices'][ $device ] = true;
				break;
			}
		}
		return array_values( $picks );
	}
}

<?php
/**
 * The page health
 *
 *
 * @since      3.0
 * @package    LiteSpeed
 * @subpackage LiteSpeed/src
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Health extends Base
{
	const TYPE_SPEED = 'speed';
	const TYPE_SCORE = 'score';

	protected static $_instance;
	protected $_summary;

	/**
	 * Init
	 *
	 * @since  3.0
	 * @access protected
	 */
	protected function __construct()
	{
		$this->_summary = self::get_summary();

	}

	/**
	 * Test latest speed
	 *
	 * @since 3.0
	 */
	private function _ping( $type )
	{
		$data = array( 'action' => $type );

		$json = Cloud::post( Cloud::SVC_HEALTH, $data, 600 );

		if ( empty( $json[ 'data' ] ) || empty( $json[ 'data' ][ 'before' ] ) || empty( $json[ 'data' ][ 'after' ] ) ) {
			Log::debug( '[Health] ❌ no data' );
			return false;
		}

		$this->_summary[ $type . '.before' ] = $json[ 'data' ][ 'before' ];
		$this->_summary[ $type . '.after' ] = $json[ 'data' ][ 'after' ];

		self::save_summary();

		Log::debug( '[Health] saved result' );
	}

	/**
	 * Generate scores
	 *
	 * @since 3.0
	 */
	public function scores()
	{
		$_health_summary = self::get_summary();

		$speed_before = $speed_after = $speed_improved = 0;
		if ( ! empty( $_health_summary[ 'speed.before' ] ) && ! empty( $_health_summary[ 'speed.after' ] ) ) {
			// Format loading time
			$speed_before = $_health_summary[ 'speed.before' ] / 1000;
			if ( $speed_before < 0.01 ) {
				$speed_before = 0.01;
			}
			$speed_before = number_format( $speed_before, 2 );

			$speed_after = $_health_summary[ 'speed.after' ] / 1000;
			if ( $speed_after < 0.01 ) {
				$speed_after = number_format( $speed_after, 3 );
			}
			else {
				$speed_after = number_format( $speed_after, 2 );
			}

			$speed_improved = ( $_health_summary[ 'speed.before' ] - $_health_summary[ 'speed.after' ] ) * 100 / $_health_summary[ 'speed.before' ];
			if ( $speed_improved > 99 ) {
				$speed_improved = number_format( $speed_improved, 2 );
			}
			else {
				$speed_improved = number_format( $speed_improved );
			}
		}

		$score_before = $score_after = $score_improved = 0;
		if ( ! empty( $_health_summary[ 'score.before' ] ) && ! empty( $_health_summary[ 'score.after' ] ) ) {
			$score_before = $_health_summary[ 'score.before' ];
			$score_after = $_health_summary[ 'score.after' ];

			// Format Score
			$score_improved = ( $score_after - $score_before ) * 100 / $score_after;
			if ( $score_improved > 99 ) {
				$score_improved = number_format( $score_improved, 2 );
			}
			else {
				$score_improved = number_format( $score_improved );
			}
		}

		return array(
			'speed_before' => $speed_before,
			'speed_after' => $speed_after,
			'speed_improved' => $speed_improved,
			'score_before' => $score_before,
			'score_after' => $score_after,
			'score_improved' => $score_improved,
		);
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance();

		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_SPEED :
			case self::TYPE_SCORE :
				$instance->_ping( $type );
				break;

			default:
				break;
		}

		Admin::redirect();
	}

}
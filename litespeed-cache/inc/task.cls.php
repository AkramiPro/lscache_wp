<?php
/**
 * The cron task class.
 *
 * @since      	1.1.3
 * @since  		1.5 Moved into /inc
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class Task
{
	private static $_instance ;

	const CRON_ACTION_HOOK_CRAWLER = 'litespeed_crawl_trigger' ;
	const CRON_ACTION_HOOK_AVATAR = 'litespeed_avatar_trigger' ;
	const CRON_ACTION_HOOK_IMGOPTM = 'litespeed_imgoptm_trigger' ;
	const CRON_ACTION_HOOK_IMGOPTM_AUTO_REQUEST = 'litespeed_imgoptm_auto_request_trigger' ;
	const CRON_ACTION_HOOK_CCSS = 'litespeed_ccss_trigger' ;
	const CRON_ACTION_HOOK_IMG_PLACEHOLDER = 'litespeed_img_placeholder_trigger' ;
	const CRON_FITLER_CRAWLER = 'litespeed_crawl_filter' ;
	const CRON_FITLER = 'litespeed_filter' ;

	/**
	 * Init
	 *
	 * @since  1.6
	 * @access private
	 */
	private function __construct()
	{
		Log::debug2( 'Task init' ) ;

		add_filter( 'cron_schedules', array( $this, 'lscache_cron_filter' ) ) ;

		// Register crawler cron
		if ( Core::config( Const::O_CRWL ) && Router::can_crawl() ) {
			// keep cron intval filter
			$this->_schedule_filter_crawler() ;

			// cron hook
			add_action( self::CRON_ACTION_HOOK_CRAWLER, '\LiteSpeed\Crawler::crawl_data' ) ;
		}

		// Register img optimization fetch ( always fetch immediately )
		if ( Core::config( Const::O_IMG_OPTM_CRON ) ) {
			self::schedule_filter_imgoptm() ;

			add_action( self::CRON_ACTION_HOOK_IMGOPTM, '\LiteSpeed\Img_Optm::cron_pull_optimized_img' ) ;
		}

		// Image optm auto request
		if ( Core::config( Const::O_IMG_OPTM_AUTO ) ) {
			self::schedule_filter_imgoptm_auto_request() ;

			add_action( self::CRON_ACTION_HOOK_IMGOPTM_AUTO_REQUEST, '\LiteSpeed\Img_Optm::cron_auto_request' ) ;
		}

		// Register ccss generation
		if ( Core::config( Const::O_OPTM_CCSS_ASYNC ) ) {
			self::schedule_filter_ccss() ;

			add_action( self::CRON_ACTION_HOOK_CCSS, '\LiteSpeed\CSS::cron_ccss' ) ;
		}

		// Register image placeholder generation
		if ( Core::config( Const::O_MEDIA_PLACEHOLDER_RESP_ASYNC ) ) {
			self::schedule_filter_placeholder() ;

			add_action( self::CRON_ACTION_HOOK_IMG_PLACEHOLDER, '\LiteSpeed\Placeholder::cron' ) ;
		}

		// Register avatar warm up
		if ( Core::config( Const::O_DISCUSS_AVATAR_CRON ) ) {
			self::schedule_filter_avatar() ;

			add_action( self::CRON_ACTION_HOOK_AVATAR, 'LiteSpeed_Avatar::cron' ) ;
		}
	}

	/**
	 * Enable/Disable cron task
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function enable()
	{
		$id = Const::O_CRWL ;

		// get new setting
		$is_enabled = ! Core::config( $id ) ;

		// log
		Log::debug( 'Crawler log: Crawler is ' . ( $is_enabled ? 'enabled' : 'disabled' ) ) ;

		// update config
		Config::get_instance()->update_options( array( $id => $is_enabled ) ) ;

		self::update() ;

		echo json_encode( array( 'enable' => $is_enabled ) ) ;
		wp_die() ;
	}

	/**
	 * Update cron status
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $options The options to check if cron should be enabled
	 */
	public static function update( $options = false )
	{
		$id = Const::O_CRWL ;
		if ( $options && isset( $options[ $id ] ) ) {
			$is_active = $options[$id] ;
		}
		else {
			$is_active = Core::config( $id ) ;
		}

		if ( ! $is_active ) {
			self::clear() ;
		}

	}

	/**
	 * Schedule cron img optm auto request
	 *
	 * @since 2.4.1
	 * @access public
	 */
	public static function schedule_filter_imgoptm_auto_request()
	{
		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_IMGOPTM_AUTO_REQUEST ) ) {
			Log::debug( 'Cron log: ......img optm auto request cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK_IMGOPTM_AUTO_REQUEST ) ;
		}
	}

	/**
	 * Schedule cron img optimization
	 *
	 * @since 1.6.1
	 * @access public
	 */
	public static function schedule_filter_imgoptm()
	{
		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_IMGOPTM ) ) {
			Log::debug( 'Cron log: ......img optimization cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK_IMGOPTM ) ;
		}
	}

	/**
	 * Schedule cron ccss generation
	 *
	 * @since 2.3
	 * @access public
	 */
	public static function schedule_filter_ccss()
	{
		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_CCSS ) ) {
			Log::debug( 'Cron log: ......ccss cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK_CCSS ) ;
		}
	}

	/**
	 * Schedule cron image placeholder generation
	 *
	 * @since 2.5.1
	 * @access public
	 */
	public static function schedule_filter_placeholder()
	{
		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_IMG_PLACEHOLDER ) ) {
			Log::debug( 'Cron log: ......image placeholder cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK_IMG_PLACEHOLDER ) ;
		}
	}

	/**
	 * Schedule cron avatar
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function schedule_filter_avatar()
	{
		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_AVATAR ) ) {
			Log::debug( 'Cron log: ......avatar cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK_AVATAR ) ;
		}
	}

	/**
	 * Schedule cron crawler
	 *
	 * @since 1.1.0
	 * @access private
	 */
	private function _schedule_filter_crawler()
	{
		add_filter( 'cron_schedules', array( $this, 'lscache_cron_filter_crawler' ) ) ;

		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_CRAWLER ) ) {
			Log::debug( 'Crawler cron log: ......cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER_CRAWLER, self::CRON_ACTION_HOOK_CRAWLER ) ;
		}
	}

	/**
	 * Register cron interval imgoptm
	 *
	 * @since 1.6.1
	 * @access public
	 * @param array $schedules WP Hook
	 */
	public function lscache_cron_filter( $schedules )
	{
		if ( ! array_key_exists( self::CRON_FITLER, $schedules ) ) {
			$schedules[ self::CRON_FITLER ] = array(
				'interval' => 60,
				'display'  => __( 'Every Minute', 'litespeed-cache' ),
			) ;
		}
		return $schedules ;
	}

	/**
	 * Register cron interval
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $schedules WP Hook
	 */
	public function lscache_cron_filter_crawler( $schedules )
	{
		$interval = Core::config( Const::O_CRWL_RUN_INTERVAL ) ;
		// $wp_schedules = wp_get_schedules() ;
		if ( ! array_key_exists( self::CRON_FITLER_CRAWLER, $schedules ) ) {
			// 	Log::debug('Crawler cron log: ......cron filter '.$interval.' added......') ;
			$schedules[ self::CRON_FITLER_CRAWLER ] = array(
				'interval' => $interval,
				'display'  => __( 'LiteSpeed Cache Custom Cron Crawler', 'litespeed-cache' ),
			) ;
		}
		return $schedules ;
	}

	/**
	 * Clear cron
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function clear()
	{
		Log::debug( 'Crawler cron log: ......cron hook cleared......' ) ;
		wp_clear_scheduled_hook( self::CRON_ACTION_HOOK_CRAWLER ) ;
	}


	/**
	 * Get the current instance object.
	 *
	 * @since 1.6
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}
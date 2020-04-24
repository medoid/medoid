<?php

class Medoid_Core_Cdn_Integration {
	protected static $instance;

	protected $real_url;
	protected $url;

	protected $cdn_provider;
	protected $cdns;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->includes();
		$this->setup_cdn();
	}

	public function includes() {
		require_once MEDOID_ABSPATH . '/includes/cdn/class-medoid-cdn-gumlet.php';
		require_once MEDOID_ABSPATH . '/includes/cdn/class-medoid-cdn-cloudimage.php';
	}

	public function setup_cdn() {
		$this->cdns = [
			'imagekit'   => Medoid_Cdn_Imagekit::class,
			'cloudimage' => Medoid_Cdn_CloudImage::class,
		];

		$cdn_provider = apply_filters( 'medoid_apply_cdn_provider', $this->cdns['gumlet'] );

		/**
		 * Create CDN Provider via class name
		 */
		$this->cdn_provider = new $cdn_provider( [] );
	}

	public function is_enabled() {
		return true;
	}

	public function delivery( $url ) {
		return $this->cdn_provider->process( $url );
	}

	public function resize() {
		return call_user_func_array(
			array( $this->cdn_provider, 'resize' ),
			func_get_args()
		);
	}

	public function get_provider() {
		return $this->cdn_provider;
	}
}

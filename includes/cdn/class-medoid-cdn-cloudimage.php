<?php
class Medoid_CDN_CloudImage extends Medoid_CDN {
	protected $processing = true;

	protected $support_url         = true;
	protected $support_proxy       = true;
	protected $support_crop        = true;
	protected $support_resize      = true;
	protected $support_filters     = true;
	protected $support_operattions = true;
	protected $support_watermark   = true;

	protected $domain = 'cloudimage.io';
	protected $token;

	public function load_options( $options = array() ) {
		$this->token = apply_filters(
			'medoid_cdn_cloudimage_token',
			array_get( $options, 'cloudimage_token', null ),
			$options
		);
	}

	public function convert_api_query( $fields ) {
		$convert_fields = array();

		foreach ( $fields as $field => $value ) {
			if ( isset( $this->api_fields_maps[ $field ] ) ) {
				$field_name                    = $this->api_fields_maps[ $field ];
				$convert_fields[ $field_name ] = $value;
			} else {
				$convert_fields[ $field ] = $value;
			}
		}
		$convert_fields['crop'] = 'entropy';

		return $convert_fields;
	}

	protected function create_url( $url, $sizes = array() ) {
		$query     = '';
		$api_query = $this->convert_api_query( $sizes );

		if ( ! empty( $api_query ) ) {
			$query .= '?' . http_build_query( $api_query );
		}

		$site      = parse_url( site_url() );
		$filters   = $this->get_filters();
		$image_url = preg_replace(
			$filters['search'],
			$filters['replace'],
			$url
		);
		return sprintf( '%s%s', $image_url, $query );
	}

	public function resize( $url, $sizes ) {
	}

	public function process( $file_path ) {
	}
}
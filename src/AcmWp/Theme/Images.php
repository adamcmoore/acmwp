<?php
namespace AcmWp\Theme;


Class Images
{

	public static function registerSizes(array $sizes)
	{
		$thumbnail = array_pull( $sizes, 'thumbnail' );

		if ($thumbnail) {
			set_post_thumbnail_size($thumbnail[0], $thumbnail[1], $thumbnail[2]);
		}

		foreach ($sizes as $size => $options) {
			add_image_size($size, $options[0], $options[1], $options[2]);
		}
	}


	public static function getImageSize($size) {
		global $_wp_additional_image_sizes;

		if (isset($_wp_additional_image_sizes[$size])) {
			return $_wp_additional_image_sizes[$size];
		}

		return false;
	}



	public static function transparentImageUrl() {
		return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
	}


	public static function transparentImage($size = '', $alt = '') {
		$size  = self::getImageSize($size);
		$image = '<img src="'.self::transparentImageUrl().'" alt="'. $alt .'" ';
		if ($size) {
			$image .=  ' style="width: '.$size['width'].'px; height: '.$size['height'].'px;" ';
		}
		$image .= ' />';

		return $image;
	}



	public static function getAttachment($attachment_id, $size = 'full') {
		$attachment = get_post($attachment_id);
		$src = wp_get_attachment_image_src($attachment->ID, $size);
		$src = $src[0];

		return [
			'alt'         => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
			'caption'     => $attachment->post_excerpt,
			'description' => $attachment->post_content,
			'href'        => get_permalink($attachment->ID),
			'src'         => $src,
			'title'       => $attachment->post_title
		];
	}


	public static function getThumbnailSrc($post_id, $size) {
		$attachment_id = get_post_thumbnail_id($post_id);
		return wp_get_attachment_image_src($attachment_id, $size);
	}


	public static function getThumbnailObject($the_post = null) {
		global $post;

		if (!$the_post) {
			$the_post = $post;
		}

	    $sizes = get_intermediate_image_sizes();

		if ($the_post->post_type === 'attachment') {
			$attachment_id = $the_post->ID;
		} else {
			$attachment_id = get_post_thumbnail_id($the_post->ID);
		}

		if (!$attachment_id) {
			return null;
		}

		$attachment = get_post($attachment_id);
		if (!$attachment) {
			return false;
		}

		$src = wp_get_attachment_image_src($attachment->ID, 'full');

		$value = [
			'id' => $attachment->ID,
			'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
			'title' => $attachment->post_title,
			'caption' => $attachment->post_excerpt,
			'description' => $attachment->post_content,
			'mime_type'	=> $attachment->post_mime_type,
			'url' => $src[0],
			'width' => $src[1],
			'height' => $src[2],
			'sizes' => [],
		];


		$image_sizes = get_intermediate_image_sizes();

		if($image_sizes) {
			foreach( $image_sizes as $image_size ) {
				$src = wp_get_attachment_image_src($attachment->ID, $image_size);

				$value[ 'sizes' ][ $image_size ] = $src[0];
				$value[ 'sizes' ][ $image_size . '-width' ] = $src[1];
				$value[ 'sizes' ][ $image_size . '-height' ] = $src[2];
			}
		}

	    return $value;
	}


}

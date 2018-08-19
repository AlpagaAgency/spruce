<?php

namespace Spruce\Model;

use Timber\Timber as Timber;
use Timber\Image as TimberImage;

class Image extends TimberImage {

	static protected function create($path,$postId=0)
	{
		$imgfile= $path;
		$filename = basename($imgfile);
		$upload_file = wp_upload_bits($filename, null, file_get_contents($imgfile));
		$attachmentId = false;
		if (!$upload_file['error']) {
			$wp_filetype = wp_check_filetype($filename, null );
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_parent' => 0,
				'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
				'post_content' => '',
				'post_status' => 'inherit'
			);
			$attachmentId = wp_insert_attachment( $attachment, $upload_file['file'], $postId );
			
			if (!is_wp_error($attachmentId)) {
				require_once(ABSPATH . "wp-admin" . '/includes/image.php');
				$attachmentData = wp_generate_attachment_metadata( $attachmentId, $upload_file['file'] );
				wp_update_attachment_metadata( $attachmentId,  $attachmentData );
			}
			
			set_post_thumbnail( $postId, $attachmentId );
		}

		return $attachmentId;
	}

	static public function createFeaturedImage($path, $postId)
	{
		return self::create($path,$postId);
	}

	static public function createFromPath($path)
	{
		return self::create($path);
	}

	public function getMimeType()
	{
		return $this->post_mime_type;
	}

	public function defineAsFeaturedImageForPost($postId)
	{
		set_post_thumbnail( $postId, $this->ID );
		return $this;
	}

	static public function defineFeaturedImageForPost($postId,$imgId)
	{
		set_post_thumbnail($postId, $imgId);
	}

}

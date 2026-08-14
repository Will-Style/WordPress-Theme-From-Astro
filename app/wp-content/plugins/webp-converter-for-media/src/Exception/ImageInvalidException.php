<?php

namespace WebpConverter\Exception;

/**
 * {@inheritdoc}
 */
class ImageInvalidException extends ExceptionAbstract {

	const ERROR_MESSAGE = '"%s" is not a valid image file.';
	const ERROR_CODE    = 'invalid_image';

	public function get_error_message( array $values ): string {
		return sprintf( self::ERROR_MESSAGE, $values[0] );
	}

	public function get_error_status(): string {
		return self::ERROR_CODE;
	}

	public function is_crashed_file_required(): bool {
		return true;
	}
}

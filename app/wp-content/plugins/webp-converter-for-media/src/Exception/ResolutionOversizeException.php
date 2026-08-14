<?php

namespace WebpConverter\Exception;

/**
 * {@inheritdoc}
 */
class ResolutionOversizeException extends ExceptionAbstract {

	const ERROR_MESSAGE = 'Image is larger than maximum 8K resolution: "%s".';
	const ERROR_CODE    = 'max_resolution';

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

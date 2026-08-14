<?php

namespace WebpConverter\Exception;

/**
 * {@inheritdoc}
 */
class FilesizeOversizeException extends ExceptionAbstract {

	const ERROR_MESSAGE = 'Image is larger than the maximum size of %1$sMB: "%2$s".';
	const ERROR_CODE    = 'max_filezile';

	public function get_error_message( array $values ): string {
		$number = (int) $values[0];
		return sprintf(
			self::ERROR_MESSAGE,
			round( $number / 1024 / 1024 ),
			$values[1]
		);
	}

	public function get_error_status(): string {
		return self::ERROR_CODE;
	}

	public function is_crashed_file_required(): bool {
		return true;
	}
}

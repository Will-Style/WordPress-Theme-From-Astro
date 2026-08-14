<?php

namespace WebpConverter\Exception;

/**
 * {@inheritdoc}
 */
interface ExceptionInterface {

	/**
	 * @param mixed[]|string $value .
	 */
	public function __construct( $value = [] );

	/**
	 * @param string[] $values .
	 *
	 * @return string
	 */
	public function get_error_message( array $values ): string;

	public function get_error_status(): string;

	public function is_crashed_file_required(): bool;
}

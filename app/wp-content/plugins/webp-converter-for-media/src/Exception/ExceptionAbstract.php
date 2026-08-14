<?php

namespace WebpConverter\Exception;

/**
 * {@inheritdoc}
 */
abstract class ExceptionAbstract extends \Exception implements ExceptionInterface {

	final public function __construct( $value = [] ) {
		$this->code = $this->get_error_status();
		parent::__construct( $this->get_error_message( (array) $value ) );
	}

	public function is_crashed_file_required(): bool {
		return false;
	}
}

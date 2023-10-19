<?php namespace MeowCrew\AddonsConditions\Frontend;

/**
 * Class AddonCondition
 *
 * @package MeowCrew\AddonsConditions\AddonCondition
 */
class AddonCondition {

	private $type;
	private $relation;
	private $conditionValue;
	private $fieldValue;

	/**
	 * AddonCondition constructor.
	 *
	 * @param string $type
	 * @param string $relation
	 * @param $conditionValue
	 * @param $fieldValue
	 */
	public function __construct( $type, $relation, $conditionValue, $fieldValue ) {
		$this->type           = $type;
		$this->relation       = $relation;
		$this->conditionValue = $conditionValue;
		$this->fieldValue     = $fieldValue;
	}

	public function check() {

		if ( in_array( $this->type, array( 'checkbox', 'multiple_choice' ) ) ) {

			$fieldValue     = (array) $this->fieldValue;
			$conditionValue = intval( $this->conditionValue );

			// Array keys starts from 0, but condition stores actual option number
			$conditionValue --;

			$isIncluded = in_array( $conditionValue, $fieldValue );

			// Relation can be either 'is' or 'is_not'. Return true for is_not if value is not selected.
			return 'is' === $this->relation ? $isIncluded : ! $isIncluded;
		}

		if ( 'file_upload' === $this->type ) {

			$isSelected = ! empty( $fieldValue['size'] ) && $fieldValue['size'] > 0;

			// Relation can be either 'is' or 'is_not'. Return true for is_not if value is not selected.
			return 'is' === $this->relation ? $isSelected : ! $isSelected;
		}

		if ( in_array( $this->type, array( 'custom_text', 'custom_textarea' ) ) ) {

			if ( 'is' === $this->relation ) {
				return $this->fieldValue === $this->conditionValue;
			}

			if ( 'is_not' === $this->relation ) {
				return $this->fieldValue !== $this->conditionValue;
			}

			if ( 'is_empty' === $this->relation ) {
				return '' === $this->fieldValue;
			}

			if ( 'is_not_empty' === $this->relation ) {
				return '' !== $this->fieldValue;
			}

			if ( 'text_contains' === $this->relation ) {
				return strpos( $this->fieldValue, $this->conditionValue ) !== false;
			}

			if ( 'text_does_not_contain' === $this->relation ) {
				return strpos( $this->fieldValue, $this->conditionValue ) === false;
			}

			if ( 'text_start_with' === $this->relation ) {
				return strpos( $this->fieldValue, $this->conditionValue ) === 0;
			}

			if ( 'text_not_start_with' === $this->relation ) {
				return strpos( $this->fieldValue, $this->conditionValue ) !== 0;
			}
		}

		if ( in_array( $this->type, array( 'custom_price', 'input_multiplier' ) ) ) {

			if ( 'is_empty' === $this->relation ) {
				return '' === $this->fieldValue;
			}

			if ( 'is_not_empty' === $this->relation ) {
				return '' !== $this->fieldValue;
			}

			// Make sure values are numbers to make math operations
			$fieldValue     = floatval( $this->fieldValue );
			$conditionValue = floatval( $this->conditionValue );

			if ( 'is' === $this->relation ) {
				return $fieldValue === $conditionValue;
			}

			if ( 'is_not' === $this->relation ) {
				return $fieldValue !== $conditionValue;
			}

			if ( 'is_greater_than' === $this->relation ) {
				return $fieldValue > $conditionValue;
			}

			if ( 'is_less_than' === $this->relation ) {
				return $fieldValue < $conditionValue;
			}

			if ( 'is_greater_than_or_equal' === $this->relation ) {
				return $fieldValue >= $conditionValue;
			}

			if ( 'is_less_than_or_equal' === $this->relation ) {
				return $fieldValue <= $conditionValue;
			}
		}

		return false;
	}
}

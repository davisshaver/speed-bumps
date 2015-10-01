<?php
namespace Speed_Bumps\Constraints\Elements;

class Blockquote extends Constraint_Abstract {
	public function paragraph_not_contains_element( $paragraph ) {
		if ( false !== stripos( $paragraph, '<blockquote' ) ) {
			return false;
		}

		return true;
	}

	public function not_inside_unclosed_element( $before, $after ) {
		if ( false === stripos( $before, 'blockquote' ) ) {
			return true;
		}
		preg_match_all( '#<\s*blockquote[^>]*>#', $before, $opening_tags, PREG_SET_ORDER );
		preg_match_all( '#<\s*/\s*blockquote[^>]*>#', $before, $closing_tags, PREG_SET_ORDER );
		if ( count( $opening_tags ) <= count( $closing_tags ) ) {
			return true;
		}

		return false;
	}
}

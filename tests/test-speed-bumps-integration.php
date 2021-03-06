<?php

class Test_Speed_Bumps_Integration extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		\Speed_Bumps()->clear_all_speed_Bumps();
	}

	public function test_speed_bump_insertion_based_on_constraint_filter() {
		register_speed_bump( 'rickroll', array(
			'string_to_inject' => function() { return '<video>This is the worst video imaginable</video>'; },
			'minimum_content_length' => false,
			'from_start' => false,
			'from_end' => false,
		) );

		add_filter( 'speed_bumps_rickroll_constraints', 'say_goodbye', 10, 4 );

		function say_goodbye( $can_insert, $context, $args, $already_inserted ) {
			if ( ! preg_match( '/give [^ ]+ up/i', $context['prev_paragraph'] ) ) {
				$can_insert = false;
			}
			return $can_insert;
		}

		$this->assertContains( '<video>This is the worst video imaginable</video>', insert_speed_bumps( $this->get_rick_rolly_content() ) );
		$this->assertNotContains( '<video>This is the worst video imaginable</video>', insert_speed_bumps( $this->get_dummy_content() ) );
		clear_speed_bump( 'rickroll' );
	}

	public function test_speed_bump_inserted_with_offset_paragraph() {
		$content = $this->get_dummy_content();

		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function() { return '<div id="polar-ad"></div>'; },
			'minimum_content_length' => false,
			'from_start' => false,
			'from_end' => false,
		) );

		$new_content = Speed_Bumps()->insert_speed_bumps( $content );
		$this->assertSpeedBumpAtParagraph( $new_content, 2, '<div id="polar-ad"></div>' );
	}

	public function test_speed_bump_inserted_with_2_offset_paragraphs() {
		$content = $this->get_dummy_content();

		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function() { return '<div id="polar-ad"></div>'; },
			'from_start' => array(
				'paragraphs' => 2,
			),
			'from_end' => false,
			'from_element' => false,
			'minimum_content_length' => array( 'characters' => 1200 ),
		) );

		$new_content = Speed_Bumps()->insert_speed_bumps( $content );
		$this->assertSpeedBumpAtParagraph( $new_content, 3, '<div id="polar-ad"></div>' );
	}

	public function test_speed_bump_not_inserted_with_content_less_than_1200() {
		$content = 'less than 1200 character';

		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function() { return '<div id="polar-ad"></div>'; },
			'from_start' => false,
			'from_end' => false,
			'minimum_content_length' => array( 'characters' => 1200 ),
		) );

		$new_content = Speed_Bumps()->insert_speed_bumps( $content );
		$this->assertNotContains( '<div id="polar-ad"></div>', $new_content );
	}

	public function test_speed_bump_should_skip_image_tag() {
		$content = $this->get_dummy_content();

		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function() { return '<div id="polar-ad"></div>'; },
			'from_start' => 3,
			'from_end' => null,
			'minimum_content_length' => null,
			'from_element' => array(
				'paragraphs' => 1, 'image',
			),
		) );

		$new_content = Speed_Bumps()->insert_speed_bumps( $content );
		$this->assertSpeedBumpAtParagraph( $new_content, 7, '<div id="polar-ad"></div>' );
	}

	public function test_two_speed_bumps_injected() {
		$content = $this->get_dummy_content();

		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function() { return 'test1'; },
			'minimum_content_length' => 1,
			'from_start' => null,
			'from_end' => null,
			'from_speedbump' => 1,
		) );

		\Speed_Bumps()->register_speed_bump( 'speed_bump2', array(
			'string_to_inject' => function() { return 'test2'; },
			'minimum_content_length' => 1,
			'from_start' => null,
			'from_end' => null,
			'from_speedbump' => 1,
		) );

		$new_content = Speed_Bumps()->insert_speed_bumps( $content );

		$this->assertSpeedBumpAtParagraph( $new_content, 2, 'test1' );
		$this->assertSpeedBumpAtParagraph( $new_content, 4, 'test2' );

	}

	public function test_clear_all_speed_bumps() {
		$content = $this->get_dummy_content();

		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function() { return 'test1'; },
			'minimum_content_length' => 1,
			'from_start' => 0,
		) );

		\Speed_Bumps()->register_speed_bump( 'speed_bump2', array(
			'string_to_inject' => function() { return 'test2'; },
			'minimum_content_length' => 1,
			'from_start' => 0,
		) );

		Speed_Bumps()->clear_all_speed_bumps();

		$new_content = Speed_Bumps()->insert_speed_bumps( $content );
		$this->assertEquals( $content, $new_content );
	}

	public function test_clear_speed_bump() {
		$content = $this->get_dummy_content();

		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function() { return 'test1'; },
			'minimum_content_length' => 1,
			'from_start' => 0,
		) );

		\Speed_Bumps()->register_speed_bump( 'speed_bump2', array(
			'string_to_inject' => function() { return 'test2'; },
			'minimum_content_length' => 1,
		) );

		Speed_Bumps()->clear_speed_bump( 'speed_bump2' );

		$new_content = Speed_Bumps()->insert_speed_bumps( $content );
		$this->assertSpeedBumpAtParagraph( $new_content, 2, 'test1' );
		$this->assertNotContains( 'test2', $new_content );

	}

	public function test_speed_bumps_should_insert_with_minimum_space_from_other_inserts() {
		$content = $this->get_dummy_content();

		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function() { return 'test1'; },
			'minimum_content_length' => 1,
			'from_start' => 0,
			'from_end' => null,
		) );

		\Speed_Bumps()->register_speed_bump( 'speed_bump2', array(
			'string_to_inject' => function() { return 'test2'; },
			'minimum_content_length' => 1,
			'from_start' => 0,
			'from_end' => null,
			'from_speedbump' => 4,
			'from_element' => array(
				'paragraphs' => 1, 'image',
			),
		) );

		$new_content = Speed_Bumps()->insert_speed_bumps( $content );

		$this->assertSpeedBumpAtParagraph( $new_content, 2, 'test1' );
		$this->assertSpeedBumpAtParagraph( $new_content, 8, 'test2' );

	}

	public function test_speed_bump_filter_can_shortcircuit_other_constraints() {
		Speed_Bumps()->register_speed_bump( 'new_sb', array( 'string_to_inject' => function() { return 'shortcircuited speed bump'; } ) );
		remove_all_filters( 'speed_bumps_new_sb_constraints' );
		add_filter( 'speed_bumps_new_sb_constraints', '__return_true', 10 );
		add_filter( 'speed_bumps_new_sb_constraints', function() { Speed_Bumps::return_false_and_skip(); return false; }, 9 );

		$content = $this->get_dummy_content();

		$this->assertNotContains( 'shortcircuited speed bump', Speed_Bumps()->insert_speed_bumps( $content ) );
	}

	public function test_speed_bump_last_ditch_insertion() {
		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function( $context ) { return ( ! empty( $context['last_ditch'] ) ) ? 'last ditch' : 'normal insert'; },
			'minimum_content_length' => 1500,
			'from_start' => 0,
			'from_end' => null,
			'last_ditch_fallback' => true,
		) );

		$content = $this->get_dummy_content();
		$new_content = Speed_Bumps()->insert_speed_bumps( $content );

		$this->assertSpeedBumpAtParagraph( $new_content, 10, 'last ditch' );
	}

	public function test_speed_bump_last_ditch_insertion_minimum_inserts() {
		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function( $context ) { return ( ! empty( $context['last_ditch'] ) ) ? 'last ditch' : 'normal insert'; },
			'minimum_inserts' => 2,
			'from_start' => array( 'paragraphs' => 6 ),
			'from_end' => null,
			'last_ditch_fallback' => true,
		) );

		$content = $this->get_dummy_content();
		$new_content = Speed_Bumps()->insert_speed_bumps( $content );

		$this->assertSpeedBumpAtParagraph( $new_content, 8, 'normal insert' );
		$this->assertSpeedBumpAtParagraph( $new_content, 11, 'last ditch' );
	}

	public function test_speed_bump_last_ditch_insertion_callable() {
		// Prepare the test case class to pass into closures (for PHP <=5.3)
		$testcase = $this;

		\Speed_Bumps()->register_speed_bump( 'speed_bump1', array(
			'string_to_inject' => function() { return 'last ditch'; },
			'minimum_content_length' => 1500,
			'from_start' => 200000,
			'from_end' => null,
			'last_ditch_fallback' => function( $context ) use ( $testcase ) {
				$testcase->assertTrue( $context['last_ditch'] );
				return true;
			},
		) );

		$content = $this->get_dummy_content();
		$new_content = Speed_Bumps()->insert_speed_bumps( $content );

		\Speed_Bumps()->clear_all_speed_Bumps();

		\Speed_Bumps()->register_speed_bump( 'speed_bump2', array(
			'string_to_inject' => function() { return 'second speed bump'; },
			'minimum_content_length' => 1500,
			'from_start' => 200000,
			'from_end' => null,
			'last_ditch_fallback' => function( $context ) use ( $testcase ) {
				$testcase->assertTrue( $context['last_ditch'] );
				return false;
			},
		) );
		$this->assertNotContains( $new_content, 'second speed bump' );
	}

	private function get_dummy_content() {
		$content = <<<EOT
Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.

Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean.

A small river named Duden flows by their place and supplies it with the necessary regelialia. It is a paradisematic country, in which roasted parts of sentences fly into your mouth.

Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.

<img src="some awesome image"></img>

The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn’t listen.

She packed her seven versalia, put her initial into the belt and made herself on the way.

When she reached the first hills of the Italic Mountains, she had a last view back on the skyline of her hometown Bookmarksgrove, the headline of Alphabet Village and the subline of her own road, the Line Lane.

Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should
EOT;
		return $content;
	}

	private function get_rick_rolly_content() {
		$content = <<<EOT
Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.

Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean.

A small river named Duden flows by their place and supplies it with the necessary regelialia. It is a paradisematic country, in which roasted parts of sentences fly into your mouth.

Even the all-powerful Pointing has no control to give it up about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.

The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn’t listen.

She packed her seven versalia, put her initial into the belt and made herself on the way.

When she reached the first hills of the Italic Mountains, she had a last view back on the skyline of her hometown Bookmarksgrove, the headline of Alphabet Village and the subline of her own road, the Line Lane.

Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should
EOT;
		return $content;
	}

	private function assertSpeedBumpAtParagraph( $content_to_test, $speed_bump_paragraph, $injected_string ) {
		$parts = preg_split( '/\n\s*\n/', $content_to_test );
		$actual_speed_bump_paragraph = array_search( $injected_string, $parts, true );

		if ( false === $actual_speed_bump_paragraph ) {
			$this->fail( 'The speed bump is not in the content' );
		}

		$this->assertEquals( $speed_bump_paragraph, ++$actual_speed_bump_paragraph );

	}
}


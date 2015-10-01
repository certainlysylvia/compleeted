<?php
	namespace Compleeted\Support;

	class Str
	{

		/**
		 *
		 * Reliable unicode string length computation.
		 *
		 * @param   string $input
		 *
		 * @return  int
		 *
		 */
		public static function size( $input )
		{
			return grapheme_strlen( $input );
		}

		/**
		 *
		 * Reliable unicode substring.
		 *
		 * @param   string      $input
		 * @param   integer     $start
		 * @param   string|null $length
		 *
		 * @return  string
		 *
		 */
		public static function substr(
			$input,
			$start = 0,
			$length = NULL
		) {
			if ( is_null( $length ) )
				return grapheme_substr(
					$input,
					$start
				);

			return grapheme_substr(
				$input,
				$start,
				$length
			);
		}

		/**
		 *
		 * Lowercase's & removes punctuation and other non-word characters from the
		 * provided string (unicode aware).
		 *
		 * @param   string $input
		 *
		 * @return  string
		 *
		 */
		public static function normalize( $input )
		{
			$output = mb_strtolower(
				$input,
				'UTF-8'
			);
			$output = preg_replace(
				'/[^\p{L}\p{N}\ ]/ui',
				'',
				$output
			);
			$output = preg_replace(
				'/\s+$/u',
				'',
				$output
			);
			$output = preg_replace(
				'/^\s+/u',
				'',
				$output
			);

			return $output;
		}

		/**
		 *
		 * Returns an array of prefixes from the input strings start at the supplied
		 * minimum length and excluding the provided stop words.
		 *
		 * @param   string $phrase
		 * @param   int    $minComplete
		 * @param   array  $stopWords
		 *
		 * @return  array
		 *
		 */
		public static function prefixesForPhrase(
			$phrase,
			$minComplete = 2,
			$stopWords = [ ]
		) {
			$words = array_filter(
				explode(
					' ',
					static::normalize( $phrase )
				),
				function ( $w ) use
				(
					$stopWords
				)
				{
					return ! in_array(
						$w,
						$stopWords
					);
				}
			);

			$prefixes = array_map(
				function ( $w ) use
				(
					$minComplete
				)
				{
					return array_map(
						function ( $l ) use
						(
							$w
						)
						{
							return static::substr(
								$w,
								0,
								$l + 1
							);
						},
						range(
							$minComplete - 1,
							static::size( $w ) - 1
						)
					);
				},
				$words
			);

			return array_unique( Ary::flatten( $prefixes ) );
		}
	}

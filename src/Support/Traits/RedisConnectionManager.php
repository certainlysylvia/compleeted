<?php
	namespace Compleeted\Support\Traits;

	use Redis;

	trait RedisConnectionManager
	{

		/**
		 *
		 * The redis client instance
		 *
		 * @var Redis
		 *
		 */
		protected $redis = NULL;

		/**
		 *
		 * Redis connection parameters.
		 *
		 * @var array
		 *
		 */
		protected $parameters = NULL;

		/**
		 *
		 * Redis connection parameters.
		 *
		 * @var integer
		 *
		 */
		protected $database = NULL;

		/**
		 *
		 * Sets and resolves a new connection to redis. It accepts a previously
		 * instantiated Redis or anything that the Redis constructor
		 * deals with: A parameters array or a connection string.
		 *
		 * @param   mixed $client
		 *
		 * @return  Redis
		 *
		 */
		public function setConnection( $client )
		{
			if ( is_string( $client ) || is_array( $client ) )
			{
				$this->redis = NULL;
				$this->parameters = $client;
			}
			else
			{
				$this->redis = $client;
			}

			return $this->resolveConnection();
		}

		/**
		 *
		 * Returns the current connection instance in use.
		 *
		 * @return Redis;
		 *
		 */
		public function getConnection()
		{
			return $this->redis;
		}

		/**
		 *
		 * Returns the current connection instance or instantiates a new one from
		 * the provided parameters or localhost defaults.
		 *
		 * @return Redis
		 *
		 */
		public function resolveConnection()
		{
			if ( ! is_null( $this->redis ) ) return $this->redis;

			$parameters = $this->parameters ?: [
				'127.0.0.1',
				6379
			];

			$database = $this->database ?: 0;

			$this->redis = new Redis();

			$this->redis->pconnect(
				...
				$parameters
			);

			$this->redis->select( $database );

			return $this->redis;
		}

		/**
		 *
		 * Alias for `resolveConnection()`.
		 *
		 * @return Redis
		 *
		 */
		public function redis()
		{
			return $this->resolveConnection();
		}
	}

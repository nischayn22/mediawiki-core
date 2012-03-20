<?php

/**
 * Abstract special page class with scaffolding for caching HTML and other values
 * in a single blob.
 *
 * Before using any of the cahing functionality, call startCache.
 * After the last call to either getCachedValue or addCachedHTML, call saveCache.
 *
 * To get a cached value or compute it, use getCachedValue like this:
 * $this->getCachedValue( $callback );
 *
 * To add HTML that should be cached, use addCachedHTML like this:
 * $this->addCachedHTML( $callback );
 *
 * The callback function is only called when needed, so do all your expensive
 * computations here. This function should returns the HTML to be cached.
 * It should not add anything to the PageOutput object!
 *
 * @since 1.20
 *
 * @file SpecialCachedPage.php
 * @ingroup SpecialPage
 *
 * @licence GNU GPL v2 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SpecialCachedPage extends SpecialPage implements ICacheHelper {

	/**
	 * CacheHelper object to which we foreward the non-SpecialPage specific caching work.
	 * Initialized in startCache.
	 *
	 * @since 1.20
	 * @var CacheHelper
	 */
	protected $cacheHelper;

	/**
	 * Sets if the cache should be enabled or not.
	 *
	 * @since 1.20
	 * @param boolean $cacheEnabled
	 */
	public function setCacheEnabled( $cacheEnabled ) {
		$this->cacheHelper->setCacheEnabled( $cacheEnabled );
	}

	/**
	 * Initializes the caching.
	 * Should be called before the first time anything is added via addCachedHTML.
	 *
	 * @since 1.20
	 *
	 * @param integer|null $cacheExpiry Sets the cache expirty, either ttl in seconds or unix timestamp.
	 * @param boolean|null $cacheEnabled Sets if the cache should be enabled or not.
	 */
	public function startCache( $cacheExpiry = null, $cacheEnabled = null ) {
		$this->cacheHelper = new CacheHelper( $this->get );

		$this->cacheHelper->setOnInitializedHandler( array( $this, 'onCacheInitialized' ) );

		$keyArgs = $this->getCacheKey();

		if ( array_key_exists( 'action', $keyArgs ) && $keyArgs['action'] === 'purge' ) {
			unset( $keyArgs['action'] );
		}

		$this->cacheHelper->setCacheKey( $keyArgs );

		if ( $this->getRequest()->getText( 'action' ) === 'purge' ) {
			$this->cacheHelper->purge();
		}

		$this->cacheHelper->startCache( $cacheExpiry, $cacheEnabled );
	}

	/**
	 * Get a cached value if available or compute it if not and then cache it if possible.
	 * The provided $computeFunction is only called when the computation needs to happen
	 * and should return a result value. $args are arguments that will be passed to the
	 * compute function when called.
	 *
	 * @since 1.20
	 *
	 * @param {function} $computeFunction
	 * @param array|mixed $args
	 * @param string|null $key
	 *
	 * @return mixed
	 */
	public function getCachedValue( $computeFunction, $args = array(), $key = null ) {
		return $this->cacheHelper->getCachedValue( $computeFunction, $args, $key );
	}

	/**
	 * Add some HTML to be cached.
	 * This is done by providing a callback function that should
	 * return the HTML to be added. It will only be called if the
	 * item is not in the cache yet or when the cache has been invalidated.
	 *
	 * @since 1.20
	 *
	 * @param {function} $computeFunction
	 * @param array $args
	 * @param string|null $key
	 */
	public function addCachedHTML( $computeFunction, $args = array(), $key = null ) {
		$this->getOutput()->addHTML( $this->cacheHelper->getCachedValue( $computeFunction, $args, $key ) );
	}

	/**
	 * Saves the HTML to the cache in case it got recomputed.
	 * Should be called after the last time anything is added via addCachedHTML.
	 *
	 * @since 1.20
	 */
	public function saveCache() {
		$this->cacheHelper->saveCache();
	}

	/**
	 * Sets the time to live for the cache, in seconds or a unix timestamp indicating the point of expiry..
	 *
	 * @since 1.20
	 *
	 * @param integer $cacheExpiry
	 */
	public function setExpirey( $cacheExpiry ) {
		$this->cacheHelper->setExpirey( $cacheExpiry );
	}

	/**
	 * Returns the variables used to constructed the cache key in an array.
	 *
	 * @since 1.20
	 *
	 * @return array
	 */
	protected function getCacheKey() {
		return array(
			$this->mName,
			$this->getLanguage()->getCode()
		);
	}

	/**
	 * Gets called after the cache got initialized.
	 *
	 * @since 1.20
	 *
	 * @param boolean $hasCached
	 */
	public function onCacheInitialized( $hasCached ) {
		if ( $hasCached ) {
			$this->getOutput()->setSubtitle( $this->cacheHelper->getCachedNotice( $this->getContext() ) );
		}
	}

}

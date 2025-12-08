<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Koyn_Logger
 *
 * Wrapper for WC_Logger
 */
class Koyn_Logger {

    /**
     * @var WC_Logger_Interface
     */
    private $logger;

    /**
     * @var array
     */
    private $context;

    /**
     * @var bool
     */
    private $debug_enabled;

    /**
     * Constructor.
     *
     * @param bool $debug_enabled Whether debug logging is enabled.
     */
    public function __construct( $debug_enabled = false ) {
        $this->logger        = wc_get_logger();
        $this->context       = array( 'source' => 'koyn_gateway' );
        $this->debug_enabled = $debug_enabled;
    }

    /**
     * Log a message.
     *
     * @param string $level Log level.
     * @param string $message Message to log.
     * @param array $context Additional context.
     */
    public function log( $level, $message, $context = array() ) {
        // If debug is disabled, only log errors or critical issues
        if ( ! $this->debug_enabled && ! in_array( $level, array( 'error', 'critical', 'emergency', 'alert' ), true ) ) {
            return;
        }

        $full_context = array_merge( $this->context, $context );
        $this->logger->log( $level, $message, $full_context );
    }

    public function debug( $message, $context = array() ) {
        $this->log( 'debug', $message, $context );
    }

    public function info( $message, $context = array() ) {
        $this->log( 'info', $message, $context );
    }

    public function warning( $message, $context = array() ) {
        $this->log( 'warning', $message, $context );
    }

    public function error( $message, $context = array() ) {
        $this->log( 'error', $message, $context );
    }
}

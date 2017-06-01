<?php
defined( 'ABSPATH' ) || exit;
getenv( 'WP_LAYER' ) || exit;

// plugin version
if ( ! defined('ASSE_CACHE_VERSION') ) {
  define( 'ASSE_CACHE_VERSION', '0.4.6' );
}

class Asse_Cache {

  public $defaults = [
    'redirect'          => true
  ];

  public function __construct() {
    // if not on the frontend
    if (getenv('WP_LAYER') !== 'frontend') {
	    return;
    }

    $this->start_ob();
  }

  public function start_ob() {
    // start buffer
    ob_start( array( $this, 'super_dupi_cache' ) );
  }

  public function super_dupi_cache( $buffer, $args ) {
    $defaults = $this->defaults;

    // if there is nothing really to cache
    if ( strlen($buffer) < 255 ) {
      return $buffer;
    }

    // avoid to interfere with api's
    if (defined( 'DOING_AJAX' ) && DOING_AJAX) {
      return $buffer;
    } elseif ( defined('XMLRPC_REQUEST' ) && XMLRPC_REQUEST) {
      return $buffer;
    } elseif ( defined('REST_REQUEST' ) && REST_REQUEST) {
      return $buffer;
    } elseif ( isset($_GET['json']) ) {
      return $buffer;
    }

    // avoid caching search, 404, or password protected
    if (is_404() || is_search() || post_password_required() || is_feed() || is_admin()) {
      return $buffer;
    }

    if (!defined('FS_CHMOD_DIR'))
      define('FS_CHMOD_DIR', (fileperms(ABSPATH) & 0777 | 0755));
    if (!defined('FS_CHMOD_FILE'))
      define('FS_CHMOD_FILE', (fileperms(ABSPATH . 'index.php' ) & 0777 | 0644));

    include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

    $fs      = new WP_Filesystem_Direct(new StdClass());
    $cache   = untrailingslashit(DATA_DIR) . '/cache';

    // cache dir
    if (!$fs->exists($cache)) {
      if (!$fs->mkdir($cache)) {
        // cannot cache
        return $buffer;
      }
    }

    // url path
    $urlPath  = $this->super_dupi_cache_url_path();

    // filters
    $excludes = [
      '..',
      '.'
    ];

    // dirs to create
    $dirs = array_diff(explode('/', $urlPath), $excludes);
    $path = $cache;

    // using most performant for loop
    $l = count($dirs);
    for ($i = 0; $i < $l; $i++) {
      if (!empty($dirs[$i])) {
        $path .= DIRECTORY_SEPARATOR . $dirs[$i];

        if (!$fs->exists($path)) {
          if (!$fs->mkdir($path)) {
            // cannot cache
            return $buffer;
          }
        }
      }
    }

    $fileName   = $this->super_dupi_cache_mobile() ? 'mobile.html' : 'desktop.html';
    $file       = $path  . DIRECTORY_SEPARATOR . $fileName;

    // 	consistent timing
    $mTime = time();

    // log
    if (preg_match('#</html>#i', $buffer)) {
      $buffer .= "\n<!-- Super Dupi Cache - Last modified: " . gmdate('D, d M Y H:i:s', $mTime) . " GMT -->\n";
    }

    // write buffer
    $fs->put_contents($file, $buffer, FS_CHMOD_FILE);
    $fs->touch($file, $mTime);

    // write .gz file, to decrease load on nginx
    if ($gz = gzopen($file . '.gz', 'wb9')) {
      gzwrite($gz, $buffer);
      gzclose($gz);
    }

    // write .br files, to decrease load on nginx
    if (function_exists('brotli_compress')) {
      $fs->put_contents($file . '.br', brotli_compress($buffer), FS_CHMOD_FILE);
    }

    if ( true === $defaults['redirect'] ) {
      header( 'Location: ' . $urlPath, true, 303 );
      exit;
    }

    // echo output
    return $buffer;
  }

  public function super_dupi_cache_mobile() {
	  // check mobile client, argh *:-)
	  return isset($_SERVER['HTTP_X_UA_DEVICE']) && $_SERVER['HTTP_X_UA_DEVICE'] == 'mobile';
  }

  public function super_dupi_cache_url_path() {
	  // strip query parameters
	  return trim( strtok( $_SERVER['REQUEST_URI'], '?' ) );
  }

}

$asse_cache = new Asse_Cache();

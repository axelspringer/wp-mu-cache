<?php

/**
 * Output buffering and caching
 *
 * Writes .html files for every request url
 */

// boostrap
super_dupi_cache_bootstrap();

// super dupi cache stuff
function super_dupi_cache() {
  // very simple conditioning
  if (!(is_home() || is_single())) {
    return;
  }

  // check mobile client, argh *:-)
  $isMobile   = isset($_SERVER['HTTP_X_UA_DEVICE']) && $_SERVER['HTTP_X_UA_DEVICE'] == 'mobile';

  // temp
  $path   = DATA_DIR . strtok($_SERVER['REQUEST_URI'],'?');
  $name   = $isMobile ? 'mobile.html' : 'desktop.html';
  $file   = $path . DIRECTORY_SEPARATOR . $name;

  // create path
  if (!file_exists($file)) {
    mkdir($path, 0700, true);
  }

  // get file handle
  $handle = fopen($file, 'w');

  // output construction
  $output = "";

  // iterate
  $levels = ob_get_level();

  // produce output
  for ($i = 0; $i < $levels; $i++) {
    $output .= ob_get_clean();
  }

  // filter output
  $output = apply_filters('final_output', $output);

  // Apply any filters to the final output
  fwrite($handle, "<!-- WP SuperDupi Cache -->\r\n" . $output);
  fclose($handle);

  // echo output
  echo $output;
}

// boostrap the super dupi cache
function super_dupi_cache_bootstrap() {
  // if only in the frontend
  if (defined('ASSE_SUPER_DUPI_CACHEDIR') && 
    getenv('WP_LAYER') === 'frontend') {
    // start buffer
    ob_start();

    // add action
    add_action('shutdown', 'super_dupi_cache', 0);
  }
}



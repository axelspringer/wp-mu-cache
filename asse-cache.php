<?php

/**
 * Output buffering and caching
 *
 * Writes .html files for every request url
 */

ob_start();

add_action('shutdown', function() {
  // temp
  $path   = sys_get_temp_dir() . $_SERVER['REQUEST_URI'];
  $file   = $path . DIRECTORY_SEPARATOR . 'index.html';

  // create path
  if (!file_exists($file)) {
    mkdir($path, 0700, true);
  }

  // get file handle
  $handle = fopen($file, "w");

  // output construction
  $output = '<!-- WP SuperDupi Cache -->';

  // iterate
  $levels = ob_get_level();

  // produce output
  for ($i = 0; $i < $levels; $i++) {
    $output .= ob_get_clean();
  }

  // filter output
  $output = apply_filters('final_output', $output);

  // Apply any filters to the final output
  fwrite($handle, $output);
  fclose($handle);

  // echo output
  echo $output;
}, 0);

<?php
/**
 * Please refer to the LICENSE file within this project to view the applicable license.
 */

/**
 * A helper class to print out nice debug messages, warnings and errors within the CLI and internet browser. It also
 * handles the printing out of variables and backtraces.
 *
 * This is a full hybrid approach so that a single function call would work within the CLI but also still format
 * nicely within the browser.
 *
 * Within the CLI, this helper also handles instances where the output is piped elsewhere. In normal circumstances,
 * a lot of the formatted text would appear as garbage when piped into a file. This ensures that no formatting takes
 * place if we know we're not in a TTY.
 *
 *@author Mark Bowker
 */
class debug
{
  /**
   * If the process extension (specifically the posix_isatty function) is unavailable, should we assume we're running
   * from a TTY shell or, for example, being piped somewhere which results in a lot garbage of garbage in the output?
   *
   * True: Will always format and colorize text.
   * False: Will remove all formatting from text before printing.
   *
   * @var bool
   */
  public static $format_if_cannot_detect_tty = false;

  /**
   * Whether we are in the CLI or not.
   *
   * @var bool
   */
  protected static $cli;

  /**
   * Whether we are in a TTY (terminal) or not.
   *
   * @var bool
   */
  protected static $tty;

  /**
   * Command Line Colors and Options
   */
  protected static $cli_colors_foreground = array(
    'black'     => 30,
    'red'       => 31,
    'green'     => 32,
    'yellow'    => 33,
    'blue'      => 34,
    'magenta'   => 35,
    'cyan'      => 36,
    'white'     => 37
  );
  protected static $cli_colors_background = array(
    'black'     => 40,
    'red'       => 41,
    'green'     => 42,
    'yellow'    => 43,
    'blue'      => 44,
    'magenta'   => 45,
    'cyan'      => 46,
    'white'     => 47
  );
  protected static $cli_decoration = array(
    'bold'          => 1,
    'underscore'    => 4,
    'blink'         => 5,
  );

  /**
   * CSS colors and options
   */
  protected static $css_colors_foreground = array(
    'black'     => 'color:#000000',
    'red'       => 'color:#FF0000',
    'green'     => 'color:#00BF03',
    'yellow'    => 'color:#FFFF00',
    'blue'      => 'color:#0000FF',
    'magenta'   => 'color:#FF00FF',
    'cyan'      => 'color:#00FFFF',
    'white'     => 'color:#FFFFFF',
  );
  protected static $css_colors_background = array(
    'black'     => 'background-color:#000000',
    'red'       => 'background-color:#FF0000',
    'green'     => 'background-color:#00FF00',
    'yellow'    => 'background-color:#FFFF00',
    'blue'      => 'background-color:#0000FF',
    'magenta'   => 'background-color:#FF00FF',
    'cyan'      => 'background-color:#00FFFF',
    'white'     => 'background-color:#FFFFFF',
  );
  protected static $css_decoration = array(
    'bold'          => 'font-weight:bold',
    'underscore'    => 'text-decoration:underscore',
    'blink'         => 'text-decoration: blink',
  );

  /**
   * Exits the running program with text to describe the file & line called it.
   */
  public static function quit()
  {
    $bt = self::get_calling_func_details();

    if (self::is_cli()) {
      $exit_text = self::cli_format((self::is_tty() ? '  EXIT  ' : 'EXIT'), 'white', 'red', 'bold');
      $m = "\n$exit_text {$bt['file']}:{$bt['line']}\n";
    }
    else {
      $m =  'quit(): TODO DEBUG';
    }

    exit($m);
  }

  /**
   * Nice debugging of any variable.
   *
   * @param mixed $val,... unlimited number of variables to print.
   */
  public static function print_var($val)
  {
    if (func_num_args() === 0)
      return;

    $bt = self::get_calling_func_details();

    // Get params
    $vars = func_get_args();
    $output = '';

    foreach ($vars as $var)
    {
      if (self::is_cli()) {
        $output .= '('.self::cli_format(gettype($var), null, null, 'bold').') '.print_r($var, TRUE).'';
      } else {
        echo 'quit(): TODO print_var';
      }
    }

    if (self::is_cli()) {
      echo self::cli_format((self::is_tty() ? "  DEBUG  " : 'DEBUG'), 'black', 'green', 'bold');
      echo self::cli_format(" {$bt['file']}:{$bt['line']}", 'white', 'black')."\n";
    }
    echo $output;

    return;
  }

  /**
   * Prints a nice error message.
   *
   * @param $message
   */
  public static function print_error($message)
  {
    if (self::is_cli()) {
      $label = self::cli_format((self::is_tty() ? "  ERROR  " : 'ERROR'), 'white', 'red');
      $m = "\n$label $message\n";
    }
    else {
      $m = 'print_error(): TODO DEBUG';
    }

    echo $m;
  }

  /**
 * Prints a nice warning message.
 *
 * @param $message
 */
  public static function print_warning($message)
  {
    if (self::is_cli()) {
      $label = self::cli_format((self::is_tty() ? "  WARNING  " : 'WARNING'), 'black', 'yellow');
      $m = "\n$label $message\n";
    }
    else {
      $m = 'print_warning(): TODO WARNING';
    }

    echo $m;
  }

  /**
   * Prints a nice message.
   *
   * @param $message
   */
  public static function print_msg($message)
  {
    if (self::is_cli()) {
      $label = self::cli_format((self::is_tty() ? "  MSG  " : 'MSG'), 'black', 'white');
      $m = "\n$label $message\n";
    }
    else {
      $m = 'print_warning(): TODO MSG';
    }

    echo $m;
  }

  /**
   * @return bool
   */
  protected static function is_cli()
  {
    if (self::$cli !== null) {
      return self::$cli;
    }

    return self::$cli = (PHP_SAPI === 'cli');
  }

  /**
   * Determine whether our CLI output is to a TTY (terminal). I.e. suitable for formatting.
   *
   * @return bool
   */
  protected static function is_tty()
  {
    if (self::$tty !== null) {
      return self::$tty;
    }

    if (!self::is_cli()) {
      return self::$tty = false;
    }

    if (!function_exists('posix_isatty')) {
      return self::$tty = (bool) self::$format_if_cannot_detect_tty;
    }

    return self::$tty = (posix_isatty((bool)STDOUT));
  }

  /**
   * Returns the backtrace frame at the given depth.
   * The default of 1 typically means the calling function of the one calling this.
   *
   * @param int $depth
   *
   * @return mixed
   */
  protected static function get_calling_func_details($depth = 1)
  {
    $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $depth + 1);

    if (!isset($bt[$depth])) {
      self::print_error("Trace depth of {$depth} does not exist. Returning top frame.");
      return array_shift($bt);
    }

    return $bt[$depth];
  }

  /**
   * @param string $text
   * @param null   $foreground
   * @param null   $background
   * @param array  $options
   *
   * @return string
   */
  protected static function cli_format($text, $foreground = null, $background = null, $options = array())
  {
    $options = !is_array($options) ? (array) $options : $options;

    foreach (array_merge(self::$cli_colors_foreground, self::$cli_decoration) as $placeholder => $code) {
      if (self::is_tty()) {
        $replace = array("\033[".$code."m", "\033[0m");
      } else {
        $replace = '';
      }
      $text = str_replace(array('{{'.$placeholder.'}}', '{{/'.$placeholder.'}}'), $replace, $text);
    }

    if (!self::is_tty()) {
      return $text;
    }

    $codes = array();

    if ($foreground && !empty(self::$cli_colors_foreground[$foreground])) {
      $codes[] = self::$cli_colors_foreground[$foreground];
    }

    if ($background && !empty(self::$cli_colors_background[$background])) {
      $codes[] = self::$cli_colors_background[$background];
    }

    if (!empty($options)) {
      foreach ($options as $option) {
        if (!empty(self::$cli_decoration[$option])) {
          $codes[] = self::$cli_decoration[$option];
        }
      }
    }

    if (empty($codes)) {
      return $text;
    }

    return sprintf("\033[%sm%s\033[0m", implode(';', $codes), $text);
  }

}

<?php
/**
 * A helper class to print out nice debug messages, warnings and errors within the CLI and internet browser. It also
 * handles the printing out of variables and backtraces.
 *
 * Within the CLI, this helper also handles instances where the output is piped elsewhere. In normal circumstances,
 * a lot of the formatted text would appear as garbage when piped into a file. This ensures that no formatting takes
 * place if we know we're not in a TTY.
 *
 *@author Mark Bowker
 */
class dbg
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
   * @var string
   */
  protected static $html_div_container_css;

  /**
   * Exits the running program with text to describe the file & line called it.
   */
  public static function quit()
  {
    $bt = self::get_calling_func_details(1);

    if (self::is_cli()) {
      $exit_text = self::cli_format((self::is_tty() ? '  EXIT  ' : 'EXIT:'), 'white', 'red', 'bold');
      $m = "\n$exit_text ".self::cli_format("{$bt['file']} [{$bt['line']}]", null, null)."\n";
    }
    else {
      $m =
        "\n<div style=\"".self::get_div_css()."color:#fff;background-color:#484848;padding:2px;margin:10px 0;\">"."
        <div style=\"".self::get_div_css()."color:#000;background-color:#fff;margin:0;\">\n".
        "<b>Exit:</b> ".$bt['file']." [".$bt['line']."]".
        "\n</div></div>\n";
      //$m =  "\n\n<br><br><b>Exit:</b> ".__FILE__." (".__LINE__.")\n";
    }

    exit($m);
  }

  /**
   * Nice detailed output of a variable, using HTML tables etc.
   *
   * @return mixed
   */
  public static function dump()
  {
    $args = func_get_args();
    array_unshift($args, 'dump');
    return call_user_func_array('self::print_var', $args);
  }

  /**
   * Nice detailed output of a variable, using PHP's print_r() function.
   *
   * @return mixed
   */
  public static function print_r()
  {
    $args = func_get_args();
    array_unshift($args, 'print_r');
    return call_user_func_array('self::print_var', $args);
  }

  /**
   * Nice detailed output of a variable, using PHP's var_export() function.
   *
   * @return mixed
   */
  public static function var_export()
  {
    $args = func_get_args();
    array_unshift($args, 'var_export');
    return call_user_func_array('self::print_var', $args);
  }

  /**
   * Nice debugging of any variable using the given human-readable output function.
   *
   * @param string  $function The variable debug output function to use. E.g. print_r, var_export, or custom dump
   * @param mixed   $val,...  unlimited number of variables to print.
   */
  protected static function print_var($function = 'print_r', $val)
  {
    if (self::is_cli() && $function == 'dump') {
      // Until I can be bothered writing a nice table dump to the cli, we'll just use print_r.
      $function = 'print_r';
    }

    $vars = array_slice(func_get_args(), 1);
    $num_args = count($vars);
    if ($num_args === 0)
      return;

    $bt = self::get_calling_func_details(3);

    $file_lines = file( $bt['file'] );
    $calling_line = $file_lines[$bt['line'] - 1]; // (zero-indexed array of lines)

    $regex = "{$bt['class']}::{$bt['function']}\\((.*)\\)\\s*;";
    $matches = array();
    preg_match("/{$regex}/", $calling_line, $matches );

    $given_var_names = array();
    if (isset($matches[1])) {
      $given_var_names = self::get_arg_names_from_function_call($matches[1]);
      if (count($given_var_names) != $num_args) {
        // Aww, looks like we've failed to deduce the given variable names.
        $given_var_names = array();
      }
    }

    $output = '';

    // Header
    if (self::is_cli()) {
      $output .= !self::is_tty() ? 'DEBUG: ' : '';
      $output .= self::cli_format("{$bt['file']} [{$bt['line']}]", 'green');
      $output .= "\n";
    } else {
      $output .=
        "\n<div style=\"".self::get_div_css()."color:#fff;background-color:#484848;margin-top:20px;font-family:Roboto,sans-serif;\">\n".
        "<p style=\"padding:3px;margin:0;font-size:13px;\">".$bt['file']." [{$bt['line']}]</p>".
        "";
    }

    // Var Dump
    $var_count = 0;
    foreach ($vars as $var_key => $var)
    {
      if (self::is_cli()) {
        if ($var_count > 0) {
          $output .= "\n";
        }
        $output .= self::cli_format('('.gettype($var).')', 'cyan');
        if (isset($given_var_names[$var_key])) {
          $output .= " ".self::cli_format("$given_var_names[$var_key]", 'yellow', null)."\n";
        }
        $output = trim($output)."\n".$function($var, TRUE)."\n";
      } else {
        $output .= "<div style=\"".self::get_div_css()."color:#000;background-color:#fff;margin:0;padding: 5px 1px 1px;\">\n";
        if (isset($given_var_names[$var_key])) {
          $output .=
            "\n<tt style=\"font-style:italic;font-size:13px;\"><span style=\"color:#990000\">(".self::get_type($var).")</span> <span style=\"color:#333\">" .
            htmlspecialchars($given_var_names[$var_key], ENT_QUOTES, 'UTF-8') .
            "\n</tt>\n";
        }
        if ($function == 'dump' && (gettype($var) == 'array' || gettype($var) == 'object')) {
          $output .=
            "\n<div style=\"margin:5px 0;font-family:monaco,'lucida console';\">\n".
            self::print_var_table($var).
            "\n</div>";
        } else {
          $output .=
            "\n<p style=\"margin:0;padding: 4px 6px 0px;\">".
            "<code style=\"display:block;background: none repeat scroll 0 0 #EAEEE5;padding: 2px 4px;color:#333;border-color: #D6D8D1;white-space: pre-wrap;\">".
            htmlspecialchars($function($var, true), ENT_QUOTES, 'UTF-8').
            "\n</code></p>";
        }

        $output .= "</div>";
      }

      $var_count++;
    }

    if (!self::is_cli()) {
      // Finish off header
      $output .= "</div>";
    }

    echo rtrim($output)."\n\n";

    return;
  }

  /**
   * @param $var
   * @return string
   */
  protected static function print_var_table(&$var)
  {
    $output = '<table style="font-size:13px;text-align:left;border-collapse:collapse;border:3px solid #666;background-color:#eee;">';
    $background_style = '';
    foreach ($var as $key => $val) {
      $background_style = empty($background_style) ? 'background-color:#ddd;' : '';
      $output .= '<tr>';
      $output .= '<td style="border:1px solid #666;padding:3px;vertical-align:top;'.$background_style.'">'.$key.'</td>';
      $output .= '<td style="border:1px solid #666;padding:3px;vertical-align:top;'.$background_style.'">'.gettype($val).'</td>';
      $output .= '<td style="border:1px solid #666;padding:3px;vertical-align:top;'.$background_style.'">';
      if (gettype($val) == 'array' || gettype($val) == 'object') {
        $output .= self::print_var_table($val);
      } else {
        $output .= '<pre>'.htmlspecialchars($val).'</pre>';
      }
      $output .= "</td></tr>";
    }

    $output .= "</table>";

    return $output;
  }

  /**
   * Prints a nice error message.
   *
   * @param $message
   */
  public static function error($message)
  {
    if (self::is_cli()) {
      $m  = self::cli_format((self::is_tty() ? " ERR " : 'ERROR:'), 'white', 'red')." ";
      $m .= self::cli_format($message, 'red')."\n";
    }
    else {
      $m =
        "\n<div style=\"".self::get_div_css()."color:#D31F1F;background-color:#fff;border:1px solid #D31F1F\">\n".
        "<b>Error</b> ".htmlspecialchars($message, ENT_QUOTES).
        "\n</div>\n";
    }

    echo $m;
  }

  /**
   * Prints a nice warning message.
   *
   * @param $message
   */
  public static function warning($message)
  {
    if (self::is_cli()) {
      $m  = "\n".self::cli_format((self::is_tty() ? " WRN " : 'WARNING:'), 'black', 'yellow');
      $m .= " ".self::cli_format($message, 'yellow')."\n";
    }
    else {
      $m =
        "\n<div style=\"".self::get_div_css()."color:#BF8405;border:1px solid #BF8405;\">\n".
        "<b>Warning</b> ".htmlspecialchars($message, ENT_QUOTES).
        "\n</div>\n";
    }

    echo $m;
  }

  /**
   * Prints a nice message.
   *
   * @param $message
   */
  public static function info($message)
  {
    if (self::is_cli()) {
      $label = self::cli_format((self::is_tty() ? " MSG " : 'INFO:'), 'black', 'white');
      $m = "\n$label $message\n";
    }
    else {
      $m =
        "\n<div style=\"".self::get_div_css()."color:#000;border:1px solid #aaa;background-color:#fff;\">\n".
        "<b>Info</b> ".htmlspecialchars($message, ENT_QUOTES).
        "\n</div>\n";
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
      self::error("Trace depth of {$depth} does not exist. Returning top frame.");
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

  /**
   * Tokenise the given argument list to obtain the given argument names/expressions
   *
   * @param $string
   * @return array
   */
  protected static function get_arg_names_from_function_call($string)
  {
    // TODO: Tokenise the entire file, then use the backtrace's line and function name to determine arg names for multiline support.

    $tokens = token_get_all('<?php func('.$string.')');
    $tokens = array_slice($tokens, 3);      // Remove the <?php func tokens
    $tokens = array_slice($tokens, 0, -1);  // Remove the last bracket

    // As far as I can see, the only special case we should be aware of is nested function calls.

    $args = array();
    $current_arg_name = '';
    $function_level = 0;
    foreach ($tokens as $token) {
      if (is_array($token)) {
        $current_arg_name .= $token[1];
      } elseif ($token == '(') {
        $current_arg_name .= '(';
        $function_level++;
      } elseif ($token == ')') {
        $current_arg_name .= ')';
        $function_level--;
      } elseif ($function_level == 0 && $token == ',') {
        $args[] = $current_arg_name;
        $current_arg_name = '';
      } else {
        $current_arg_name .= $token;
      }
    }

    $current_arg_name = trim($current_arg_name);
    if (!empty($current_arg_name)) {
      $args[] = $current_arg_name;
    }

    return array_map('trim', $args);
  }

  /**
   * @return string
   */
  protected static function get_div_css()
  {
    if (self::$html_div_container_css !== null) {
      return self::$html_div_container_css;
    }

    $html = "width:100%;padding:1px;margin-top:1px;font-family:Roboto,sans-serif;font-size:13px;".
      "box-sizing:border-box;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;".
      "background-color:#fff;";

    return self::$html_div_container_css = $html;
  }

  /**
   * @param $var
   * @return string
   */
  protected static function get_type(&$var)
  {
    return is_object($var) && is_callable($var) ? 'function' : gettype($var);
  }
}

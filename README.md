# DBG Dumper #

A prettified PHP variable dumper. Outputs nicely to the browser and CLI (included piped output). Does not replace `var_export` or `print_r`, but merely acts a wrapper to these core PHP functions.

## About ##

For each argument provided, it spits out the file name, line number, variable type, variable name and value. The output always displays nicely even if through a browser, a terminal or even piped into `less` or into a file. 

The unique selling point here is the output of the variable name or the given expression. This gives quick visibility of many variables at once (rather than trying to remember which order you dumped your vars).

## Install ##

Create a [composer.json](http://packagist.org/about-composer) file for your project, and include the following:

```json
{
    "require": {
        "mrlunar/php-dbg-dumper": "dev-master"
    }
}
```

## Examples ##

The following code:

```php
$my_var = array('my array var');
dbg::print_r($my_var);
```

would output in the browser like so:

![](https://raw.github.com/wiki/MrLunar/php-dbg-dumper/img/2014-02-19%2017_07_34.png)

yet would output in the terminal like so:

![](https://raw.github.com/wiki/MrLunar/php-dbg-dumper/img/2014-02-19%2017_10_19.png)

Also for example, the expression:

```php
dbg::print_r(rand(1, 50));
```

would output to the browser:

![](https://raw.github.com/wiki/MrLunar/php-dbg-dumper/img/2014-02-19%2017_23_12.png)

## Other Commands ##

```php
dbg::var_export($my_var);
```

Same as `dbg::print_r($my_var);` but using PHP's `var_export()` output.

```php
dbg::quit();
```
A verbose exit() so you don't lose yourself in a whitescreen.

![](https://raw.github.com/wiki/MrLunar/php-dbg-dumper/img/2014-02-19%2017_14_01.png)

```php
dbg::error('a terrible error has occured, run for the hills');
dbg::warning('just an FYI because this will probably cause problems later');
dbg::info('I just want to talk');
```
Should you feel these are useful in your scripts:

![](https://raw.github.com/wiki/MrLunar/php-dbg-dumper/img/2014-02-19%2017_15_39.png)

```php
dbg::dump($my_var);
```
*Experimental.*  Like `dbg::print_r()` but outputs arrays and objects into a tabular format.

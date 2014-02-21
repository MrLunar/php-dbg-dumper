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

![2014-02-19 17_07_34-mozilla firefox](https://f.cloud.github.com/assets/4201834/2209164/6aed0e56-9988-11e3-8855-a60fc23f95a2.png)

yet would output in the terminal like so:

![2014-02-19 17_10_19-os x mavericks - vmware workstation](https://f.cloud.github.com/assets/4201834/2209193/cfadf756-9988-11e3-8cd3-05ff2732dae5.png)

Also for example, the expression:

```php
dbg::print_r(rand(1, 50));
```

would output to the browser:

![2014-02-19 17_23_12-mozilla firefox](https://f.cloud.github.com/assets/4201834/2209323/8c7502f2-998a-11e3-810b-16a362f40cb3.png)

## Other Commands ##

```php
dbg::var_export($my_var);
```

Same as `dbg::print_r($my_var);` but using PHP's `var_export()` output.

```php
dbg::quit();
```
A verbose exit() so you don't lose yourself in a whitescreen.
![2014-02-19 17_14_01-mozilla firefox](https://f.cloud.github.com/assets/4201834/2209219/461a02cc-9989-11e3-8329-2b8c8ab74619.png)

```php
dbg::error('a terrible error has occured, run for the hills');
dbg::warning('just an FYI because this will probably cause problems later');
dbg::info('I just want to talk');
```
Should you feel these are useful in your scripts:
![2014-02-19 17_15_39-mozilla firefox](https://f.cloud.github.com/assets/4201834/2209238/887a5482-9989-11e3-8fd8-198f7af49d00.png)

```php
dbg::dump($my_var);
```
*Experimental.*  Like `dbg::print_r()` but outputs arrays and objects into a tabular format.

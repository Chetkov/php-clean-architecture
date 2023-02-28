# PHP Clean Architecture [EN]
A tool for automating quality control over the architecture of applications written in PHP, as well as simplifying the analysis and visualization of some metrics.

The idea of its creation was inspired by the book "Clean Architecture" (by Robert Martin).
If you haven’t read it yet, you can get acquainted with its key ideas on which the tool is based https://habr.com/en/post/504590/

## Installation
```shell script
composer require v.chetkov/php-clean-architecture
```

## Configuration
Next, copy the sample config to the root of the project
```shell script
cp vendor/v.chetkov/php-clean-architecture/example.phpca-config.php phpca-config.php
```

All configuration details are described in detail in the config sample https://github.com/Chetkov/php-clean-architecture/blob/master/example.phpca-config.php, as well as in articles https://habr.com/ru/post/504590/ and https://habr.com/ru/post/686236/

## Usage

1. Generating a report for analysis.
```shell script
vendor/bin/phpca-build-reports {?path/to/phpca-config.php}
```
The report visualizes the current state of the project, visually displays the relationships between components, their strength, the remoteness of components from the main sequence, and also highlights unwanted dependencies and other architectural problems detected based on the config.
![image](https://user-images.githubusercontent.com/12594577/134708940-f53dc72e-8664-4e57-a3a7-4f6bb4ec965c.png)
![image](https://user-images.githubusercontent.com/12594577/134709361-fbe654bd-70f4-460c-a107-fb3956f064b0.png)

2. Check for CI.
```shell script
vendor/bin/phpca-check {?path/to/phpca-config.php}
```
In case of violation by the code of restrictions specified by the config, informs of the discovered problems and completes the execution with the error.
It is recommended to add the launch of this command in the CI process (this guarantees the correspondence of the code that gets into the assembly, configured restrictions)

3. Allowed state.
```shell script
vendor/bin/phpca-allow-current-state {?path/to/phpca-config.php}
```
The command will record the current state of the project, the relationship between existing classes, to the file. With subsequent phpca-check launches, problems related to the preserved state will be ignored.

This makes it possible to easily connect php-clean-architecture not only to new projects, but also to already workers, which already have many problems, the solving of which takes time.

4. Report/Check on the file list

If you want to check for problems or build a dependence graph and conduct an analysis not on the entire project, but by some part of it (for example, according to the list of changed files), you can set the value of the environment variable *PHPCA_ALLOWED_PATHS*
Example of use:
```shell
export PHPCA_ALLOWED_PATHS=`git diff master --name-only` PHPCA_REPORTS_DIR='phpca-report'; vendor/bin/phpca-build-reports {?path/to/phpca-config.php}
```
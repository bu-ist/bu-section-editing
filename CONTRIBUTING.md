
# Building

All Javascript is compiled using [Grunt](http://gruntjs.com/). Assuming that both [Node & Node Package Manager](http://nodejs.org/) are installed, run the following commands using Terminal in this directory:

```bash
# Install Grunt CLI globally
$ npm install -g grunt-cli

# Install this project's dependencies
$ npm install

# Run Grunt
$ grunt
$ grunt watch
```

# Testing

Unit tests for this plugin live in the `tests/phpunit` directory. To run:

1. Install [Composer](https://getcomposer.org/)
2. Install [PHPUnit](https://phpunit.de/) (`composer install`)
3. Install a supported core WP Unit Test library and configure a test install (`bash bin/install-wp-tests.sh wordpress_test root '' localhost 6.9`). The default Composer install uses PHPUnit 8 and supports WordPress 5.9 through 6.9; the installer defaults to 6.9. Prefix the command with `WP_MULTISITE=1` to configure a multisite run.
4. Run the tests (`./vendor/bin/phpunit`)

The minimum-version compatibility lane runs WordPress 5.8.17 on PHP 7.4 with PHPUnit 7.5.20. WordPress 5.8 rejects PHPUnit 8, so this lane resolves the PHPUnit 7 dependency separately while leaving the default lock file on PHPUnit 8. Use separate `WP_CORE_DIR`, `WP_TESTS_DIR`, and database values when reproducing the WordPress 5.8 lane locally so an existing modern test installation is not reused.

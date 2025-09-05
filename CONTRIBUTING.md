# Contributing

## Coding Standards

This project follows the [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards).

### Setup
1. Install PHP_CodeSniffer and the WordPress standard:
   ```bash
   composer global require wp-coding-standards/wpcs squizlabs/php_codesniffer
   phpcs --config-set installed_paths $(composer global config home)/vendor/wp-coding-standards/wpcs
   ```

### Running PHPCS
Run the sniffer from the project root:
```bash
phpcs
```

Fix fixable issues automatically with:
```bash
phpcbf
```

# Transmatic: Automated Real-Time Text Translations for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andrewdwallo/transmatic.svg?style=flat-square)](https://packagist.org/packages/andrewdwallo/transmatic)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andrewdwallo/transmatic/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andrewdwallo/transmatic/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/andrewdwallo/transmatic/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/andrewdwallo/transmatic/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andrewdwallo/transmatic.svg?style=flat-square)](https://packagist.org/packages/andrewdwallo/transmatic)

Transmatic is your one-stop solution for integrating real-time text translations in Laravel applications. Whether you're building a complex SaaS, ERP, or Accounting software, Transmatic offers a range of customization options to fit your needs. By default, we leverage [AWS Translate](https://aws.amazon.com/translate/), but the package is designed to be flexible for any translation service you prefer.

## Installation

Start by installing the package via Composer:

```bash
composer require andrewdwallo/transmatic
```

After the package is installed, run the following command:

```bash
php artisan transmatic:install
```

## Preparing Your Application

### Configuring AWS Translate (Default Service)

The package leverages [AWS Translate](https://aws.amazon.com/translate/) by default. Make sure you've read the [AWS Service Provider for Laravel](https://github.com/aws/aws-sdk-php-laravel) package's documentation and have configured the following environment variables:

```dotenv
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_REGION=your-region  # default is us-east-1
```
These are essential for the AWS SDK to authenticate and interact with AWS services. Once these are set, you don't need to do anything else for AWS Translate to work.

### Configuring Your Translation Service

Transmatic is designed to be flexible for any translation service you prefer. By default, we leverage AWS Translate, but you can easily swap this out for any other service. To do so, you'll need to create a new class that implements the `Wallo\Transmatic\Contracts\Translator` contract.

```php
namespace Your\Namespace;

use Wallo\Transmatic\Contracts\Translator;

class YourTranslator implements Translator
{
    public function translate(string $text, string $from, string $to): string
    {
        // Your translation logic here
    }
}
```

Once you've created your translator, you'll need to update the `translator` key in the `transmatic.php` config file to point to your new class.

```php
'translator' => Your\Namespace\YourTranslator::class,
```

### Configuration Options

#### Source Locale

The source locale is the language code from which all translations to other languages will be made. This should typically match your application's primary language. By default, this is set to `en`.

```php
'source_locale' => env('TRANSMATIC_SOURCE_LOCALE', 'en'),
```

#### Translation Storage

Transmatic supports multiple methods for storing translations, either in cache or in JSON language files. By default, translations are stored in the cache.
```php
'storage' => env('TRANSMATIC_STORAGE', 'cache'),
```

#### Cache Configuration

If you are using cache storage, you can specify the cache duration and cache key prefix here. By default, translations are cached for 30 days.
```php
'cache' => [
    'duration' => env('TRANSMATIC_CACHE_DURATION', 60 * 24 * 30),
    'key' => env('TRANSMATIC_CACHE_KEY', 'translations'),
],
```

#### File Configuration

If you are using file storage, you can specify the path where your JSON language files will be stored.
```php
'file' => [
    'path' => env('TRANSMATIC_FILE_PATH', 'resources/data/lang'),
],
```

#### Batching Configuration

Batch processing is the default behavior for Transmatic. The relevant options include queue name, chunk size, and handling of failed translations.
```php
'batching' => [
    'queue' => env('TRANSMATIC_BATCHING_QUEUE', 'translations'),
    'chunk_size' => env('TRANSMATIC_BATCHING_CHUNK_SIZE', 50),
    'allow_failures' => env('TRANSMATIC_BATCHING_ALLOW_FAILURES', true),
],
```

For more information, you may refer to the `config/transmatic.php` file.

## Usage

### Translating Text

The `translate` method provides an easy way to translate a single string of text. You can specify the target locale as an optional argument. If not specified, the application's current locale will be used.

```php
use Wallo\Transmatic\Facades\Transmatic;

$translatedText = Transmatic::translate('Hello World', 'es'); // Hola Mundo
```
This method also updates your Source Locale's translations based on the text passed in, ensuring that new strings are stored for future use.

### Translating Multiple Strings

For translating multiple strings at once, use the `translateMany` method. This method accepts an array of strings to translate, as well as an optional target locale. If not specified, the application's current locale will be used.

```php
use Wallo\Transmatic\Facades\Transmatic;

$texts = ['Hello World', 'Goodbye World'];

$translatedTexts = Transmatic::translateMany($texts, 'fr'); // ['Bonjour le monde', 'Au revoir le monde']
```
Like the `translate` method, this will also update your Source Locale's translations based on the text passed in.

### Fetching Supported Locales

To retrieve a list of supported locales, use the `getSupportedLocales` method. This method will return an array of locales supported by your application. For example, if in your specified file path for storing translations you have a `fr.json` file, this method will return `['en', 'fr']`.

```php
use Wallo\Transmatic\Facades\Transmatic;

$supportedLocales = Transmatic::getSupportedLocales(); // ['en', 'fr']
```

### Fetching Supported Languages

To retrieve a list of supported languages along with their corresponding locales, use the `getSupportedLanguages` method. This method returns an associative array where the key is the locale and the value is the displayable name of the language. You can also pass a display locale as an optional parameter to get the language names in a specific language. If no display locale is specified, the application's current locale is used.

```php
use Wallo\Transmatic\Facades\Transmatic;

$supportedLanguages = Transmatic::getSupportedLanguages();
// Output: ['en' => 'English', 'fr' => 'French']

$supportedLanguages = Transmatic::getSupportedLanguages('fr');
// Output: ['en' => 'Anglais', 'fr' => 'Français']
```

### Getting Language from Locale

You can get the displayable name of a language from a locale using the `getLanguage` method. This method takes in the locale you're interested in and an optional display locale parameter. If no display locale is specified, it defaults to the application's current locale.

```php
use Wallo\Transmatic\Facades\Transmatic;

$language = Transmatic::getLanguage('de'); 
// Output: 'Deutsch'

$language = Transmatic::getLanguage('de', 'en'); 
// Output: 'German'
```

### Global Helper

For quick and easy translations, you may use the `translate()` and `translateMany()` helper functions.
```php
$translatedText = translate('Hello World', 'es'); // Hola Mundo

$translatedTexts = Transmatic::translateMany(['Hello World', 'Goodbye World'], 'fr'); // ['Bonjour le monde', 'Au revoir le monde']
```

### Behind the Scenes

#### Managing Translations

When you call the `translate` or `translateMany` methods, Transmatic will first check to see if the translation already exists in your application's source locale. If it does, it will process and return the translations for the specified target locale. If not, it will update your source locale's translations with the new text, and then continue with the translation process.

Transmatic checks if a batch translation process is running for the target locale you specify. If a batch is running, the package fetches the translations from either cache or JSON language files, depending on your configuration.

#### New vs. Existing Translations

For new target locales, Transmatic initiates a queued batch translation process managed by the underlying `TranslateService` class. This allows the package to efficiently handle large volumes of text for translation in one go, thanks to a queuing mechanism.

For existing target locales where most translations are already in place, the `dispatchSync` method is used for immediate, synchronous translation.

#### Importance of a Robust Source Locale

To make the most out of the batch processing feature for new target locales, it's recommended to have a well-populated source locale language file. While the code ensures that the source locale is up-to-date before proceeding with translations, having a robust set of translations in the source locale maximizes the efficiency of the batch processing for new languages.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Andrew Wallo](https://github.com/andrewdwallo)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

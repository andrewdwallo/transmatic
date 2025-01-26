![Transmatic Banner](art/transmatic-banner.png)

<p align="center">
    <a href="https://laravel.com"><img alt="Laravel v10.x" src="https://img.shields.io/badge/Laravel-v10.x-FF2D20?style=for-the-badge&logo=laravel"></a>
    <a href="https://php.net"><img alt="PHP 8.1" src="https://img.shields.io/badge/PHP-8.1-777BB4?style=for-the-badge&logo=php"></a>
    <a href="https://packagist.org/packages/andrewdwallo/transmatic"><img alt="Latest Version on Packagist" src="https://img.shields.io/packagist/v/andrewdwallo/transmatic.svg?style=for-the-badge"></a>
    <a href="https://packagist.org/packages/andrewdwallo/transmatic"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/andrewdwallo/transmatic.svg?style=for-the-badge"></a>
</p>

Transmatic is a Laravel package for real-time machine translation, enabling instant and dynamic translation across your
entire application. Suitable for projects ranging from simple websites to complex SaaS platforms and more, Transmatic
offers customization and flexibility. Using advanced machine translation, it makes your app globally accessible.
While [AWS Translate](https://aws.amazon.com/translate/) is the default engine, the package can easily integrate with
other translation services.

## Common Use Cases

### ⚡️ Application Auto-Translation

With this package, developers can automatically translate their entire application to multiple languages using services
like AWS Translate. Say goodbye to manually specifying translations for each language and achieve a multilingual
platform in minutes.

#### Benefits

- **Speed** - Translate your application in minutes.
- **Auto Locale Management** - The package manages and updates the source locale translations based on the provided
  text.

### 👤 Personalized User Experience

Empower users to customize their experience by selecting their preferred language. Once selected, the application
dynamically adjusts its locale.

#### Benefits

- **Enhanced User Experience** - Interact in the user's native language.
- **Real-Time Translation** - Adapt instantly to the user's language selection.

### 🏢 SaaS Tenant-Specific Translations

Optimize the experience for SaaS businesses by offering tenant-specific translations. Each tenant can view their
dashboard in their desired language.

#### Benefits

- **Personalization** - Address each tenant's language choice.
- **Engagement Boost** - Increase interaction by presenting content in the tenant's chosen language.

### 🛍️ E-Commerce for a Global Audience

Position your e-commerce platform or global marketplace for worldwide reach. Offer product descriptions, reviews, and
more in numerous languages.

#### Benefits

- **Global Reach** - Cater to a global audience.
- **Enhanced Sales** - Improve conversion rates by engaging customers in their native language.

## Installation

Start by installing the package via Composer:

```bash
composer require andrewdwallo/transmatic
```

After the package is installed, run the following command:

```bash
php artisan transmatic:install
```

## Setting Up Transmatic

### Queue and Batch Processing Setup

This package utilizes Laravel's queue jobs and batch processing features. The specific setup requirements depend on the
queue driver configured in your Laravel application.

#### Database Queue Driver

If you are using the database queue driver, you'll need the following tables in your database:

- `jobs`: For managing queued jobs.
- `job_batches`: For batch processing.

If these tables are not already present in your database, you can create them by running the following commands:

```bash
php artisan queue:table
php artisan queue:batches-table
```

Once the tables are created, run the following command to migrate them:

```bash
php artisan migrate
```

For users utilizing other queue drivers (such as redis, sqs, beanstalkd, etc.), refer to
the [official Laravel documentation on queues](https://laravel.com/docs/10.x/queues#introduction) for specific setup
instructions.

> 🚧 It's important to configure and manage the queue system as per your application's requirements. Proper configuration
> ensures efficient handling of background jobs and tasks by the package.

### AWS Translate Integration

By default, the package leverages [AWS Translate](https://aws.amazon.com/translate/). Ensure you've set the necessary
configurations as specified in the [AWS Service Provider for Laravel](https://github.com/aws/aws-sdk-php-laravel)
documentation, and have the following environment variables:

```dotenv
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_REGION=your-region  # default is us-east-1
```

These are essential for the AWS SDK to authenticate and interact with AWS services. Once these are set, you don't need
to do anything else for AWS Translate to work.

### Custom Translation Service Integration

While AWS Translate is the default, Transmatic allows integration with other translation services. For integration,
create a class that adheres to the `Wallo\Transmatic\Contracts\Translator` contract and update the `transmatic.php`
config file accordingly.

### Configuration Overview

Several configuration options are available, including setting the source locale, defining translation storage methods (
cache or JSON files), and specifying batch processing behavior. Refer to the `config/transmatic.php` file for a
comprehensive look.

## Using Transmatic

### Translating Text

The `translate` method provides an easy way to translate a single string of text. It allows you to optionally specify
replacement data for placeholders as well as the target locale. If the target locale is not specified, the application's
current locale will be used.

#### Using Traditional Arguments:

```php
use Wallo\Transmatic\Facades\Transmatic;

$translatedText = Transmatic::translate('Hello World', [], 'es'); // Hola Mundo
```

#### Using Named Arguments (PHP 8.0+):

```php
use Wallo\Transmatic\Facades\Transmatic;

$translatedText = Transmatic::translate(text: 'Hello World', to: 'es'); // Hola Mundo
```

This method also updates the translations in your Source Locale based on the text passed in, ensuring that new strings
are stored for future use.

### Translating Multiple Strings

For translating multiple strings at once, use the `translateMany` method. This method accepts an array of strings to
translate, as well as an optional target locale. If not specified, the application's current locale will be used.

```php
use Wallo\Transmatic\Facades\Transmatic;

$texts = ['Hello World', 'Goodbye World'];

$translatedTexts = Transmatic::translateMany(texts: $texts, to: 'fr'); // ['Bonjour le monde', 'Au revoir le monde']
```

Like the `translate` method, this method will also update the translations in your Source Locale based on the text
passed in.

### Using Translation Placeholders

You may use placeholders in your translations. To do so, use the `:placeholder` syntax in your translation strings. When
translating, pass in an array of values to replace the placeholders.

```php
use Wallo\Transmatic\Facades\Transmatic;

$translatedText = Transmatic::translate(text: 'Hello :name', replace: ['name' => 'John'], to: 'es'); // Hola John
```

### Fetching Supported Locales

To retrieve a list of supported locales, use the `getSupportedLocales` method. This method will return an array of
locales supported by your application. For example, if in your specified file path for storing translations you have a
`fr.json` file, this method will return `['en', 'fr']`.

```php
use Wallo\Transmatic\Facades\Transmatic;

$supportedLocales = Transmatic::getSupportedLocales(); // ['en', 'fr']
```

### Fetching Supported Languages

To retrieve a list of supported languages along with their corresponding locales, use the `getSupportedLanguages`
method. This method returns an associative array where the key is the locale and the value is the displayable name of
the language. You can also pass a display locale as an optional parameter to get the language names in a specific
language. If no display locale is specified, the application's current locale is used.

```php
use Wallo\Transmatic\Facades\Transmatic;

$supportedLanguages = Transmatic::getSupportedLanguages();
// Output: ['en' => 'English', 'fr' => 'French']

$supportedLanguages = Transmatic::getSupportedLanguages(displayLocale: 'fr');
// Output: ['en' => 'Anglais', 'fr' => 'Français']
```

### Getting Language from Locale

You can get the displayable name of a language from a locale using the `getLanguage` method. This method takes in the
locale you're interested in and an optional display locale parameter. If no display locale is specified, it defaults to
the application's current locale.

```php
use Wallo\Transmatic\Facades\Transmatic;

$language = Transmatic::getLanguage(locale: 'de'); 
// Output: 'Deutsch'

$language = Transmatic::getLanguage(locale: 'de', displayLocale: 'en'); 
// Output: 'German'
```

### Global Helper

For quick and easy translations, you may use the `translate()` and `translateMany()` helper functions.

```php
$translatedText = translate(text: 'Hello World', to: 'es'); // Hola Mundo

$translatedTexts = translateMany(texts: ['Hello World', 'Goodbye World'], to: 'fr'); // ['Bonjour le monde', 'Au revoir le monde']
```

## Overriding the Global Locale

If you want to override the default locale for all translation methods that do not have a specified `$to` parameter, you
can use the `setGlobalLocale` method. This will set a global locale override, ensuring that the provided locale is used
as the default for translations.

### Setting the Global Locale in a Service Provider

In your Service Provider's `boot` method, you can call the `setGlobalLocale` method to set the global locale override.

```php
use Wallo\Transmatic\Facades\Transmatic;

public function boot()
{
    Transmatic::setGlobalLocale(locale: 'fr');
}

```

When you use the translation methods after setting this global locale, they will default to this overridden locale
unless another locale is specified.

#### Example

```php
use Wallo\Transmatic\Facades\Transmatic;

Transmatic::setGlobalLocale(locale: 'fr');

$translatedText = Transmatic::translate(text: 'Hello World'); // Bonjour le monde
```

Remember, specifying a locale in the translation methods will always take precedence over the global locale override.

```php
use Wallo\Transmatic\Facades\Transmatic;

Transmatic::setGlobalLocale(locale: 'fr');

$translatedText = Transmatic::translate(text: 'Hello World', to: 'es'); // Hola Mundo
```

## Processing Missing Translations

To ensure all your translations are up-to-date, especially when there are missing translations for certain locales, you
can utilize the functionality to process missing translations. This will help in generating the missing translations for
all the supported locales excluding the source locale.

### Using the Facade in a Service Provider

In your Service Provider's `boot` method, you have two options when calling the facade to process missing translations:

#### Specifying Locales

You can process missing translations for a specific list of locales using the `processMissingTranslationsFor` method.

```php
use Wallo\Transmatic\Facades\Transmatic;

public function boot()
{
    Transmatic::processMissingTranslationsFor(locales: ['fr', 'de']);
}
```

#### Processing All Supported Locales

You can process all the missing translations for all the supported locales excluding the source locale using the
processMissingTranslations method.

```php
use Wallo\Transmatic\Facades\Transmatic;

public function boot()
{
    Transmatic::processMissingTranslations();
}
```

### Using the Artisan Command

If you would prefer to manually trigger the processing of missing translations, you may use the console command
`transmatic:process-missing-translations`. This command will provide you with a list of all the supported locales
excluding the source locale, and you can choose which locales you would like to process. You may choose to process all
the locales at once by selecting the `All` option.

This command is especially useful during development when you're adding new translations to your source locale and want
to process the missing translations for all the supported locales.

```bash
php artisan transmatic:process-missing-translations
```

### Cleaning Translations

You can use the `transmatic:clean-translations` command to remove keys from translation files that are no longer present
in the source locale. This helps keep your translations consistent and avoids unused entries.

Run the command with:

```bash
php artisan transmatic:clean-translations
```

This will clean all supported locales, excluding the source locale.

## Behind the Scenes

### Managing Translations

When you call the `translate` or `translateMany` methods, Transmatic will first check to see if the translation already
exists in your application's source locale. If it does, it will process and return the translations for the specified
target locale. If not, it will update your source locale's translations with the new text, and then continue with the
translation process.

Transmatic checks if a batch translation process is running for the target locale you specify. If a batch is running,
the package fetches the translations from either cache or JSON language files, depending on your configuration.

### New vs. Existing Translations

For new target locales, Transmatic initiates a queued batch translation process managed by the underlying
`TranslateService` class. This allows the package to efficiently handle large volumes of text for translation in one go,
thanks to a queuing mechanism.

For existing target locales where most translations are already in place, the `dispatchSync` method is used for
immediate, synchronous translation.

### Importance of a Robust Source Locale

To make the most out of the batch processing feature for new target locales, it's recommended to have a well-populated
source locale language file. While the code ensures that the source locale is up-to-date before proceeding with
translations, having a robust set of translations in the source locale maximizes the efficiency of the batch processing
for new languages.

## Contributing

Thank you for considering contributing to Transmatic! Follow these steps to get started:

1. **Fork the Repository**: Fork this repository to your GitHub account.
2. **Create a Fresh Laravel Project**: If you don't already have a Laravel project set up, create one.
3. **Clone Your Forked Repository**: Clone your forked Transmatic repository into your Laravel application's root
   directory.

```bash
git clone https://github.com/your-github-username/transmatic.git
```

4. **Create a New Branch**: Inside the '/transmatic' directory, create a branch for your fix or feature. For instance,
   if you're working on an error message fix, you might name your branch `fix/error-message`.

```bash
git checkout -b fix/error-message
```

5. **Install the Package Locally**: Update your application's `composer.json` file to include the local package. Use the
   `dev` prefix followed by your branch's name:

```jsonc
{
    // ...
    "require": {
        "andrewdwallo/transmatic": "dev-fix/error-message"
    },
    "repositories": [
        {
            "type": "path",
            "url": "transmatic/"
        }
    ],
    // ...
}
```

6. **Update Composer**: Run `composer update` to install the local version of the package in your Laravel project.

Once you've made your changes, commit them, push to your branch, and then create a pull request. Your contributions are
highly valued and appreciated!

## Need Help?

Thank you for your interest in Transmatic! Whether you're just getting started, have spotted a bug, or are thinking of a
new feature, here's how you can get help:

### 🐛 Spotted a Bug?

If you think you've found a bug in Transmatic:

1. First, check the [Issues](https://github.com/andrewdwallo/transmatic/issues) section to see if someone else has
   already reported the same problem.
2. If it's an unreported bug, please open a new issue, providing as much detail as possible, including steps to
   reproduce the issue.
3. Feel free to fix the bug yourself! Follow the [Contributing](#contributing) guidelines to get started.

### 🙋‍♂️ Have a Question or Feature Request?

If you have questions about how to use Transmatic or ideas for new features:

1. Start by checking the [Documentation](#installation) to see if your question is already answered.
2. For specific questions or general discussion,
   visit [Discussions](https://github.com/andrewdwallo/transmatic/discussions).
    - Have a question? Head over to [Q&A](https://github.com/andrewdwallo/transmatic/discussions/categories/q-a).
    - Want to share an idea or feature request? Share it
      in [Feature Requests](https://github.com/andrewdwallo/transmatic/discussions/categories/feature-requests).

### 🔐 Discovered a Security Vulnerability?

Security is a top priority. If you discover any issue regarding security:

1. Please **DO NOT** open an issue on GitHub. Disclosing security vulnerabilities publicly can be harmful.
2. Instead, review the [Security Policy](../../security/policy) for instructions on how to report a security
   vulnerability.
3. I'm dedicated to keeping users safe and will address valid security concerns diligently.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

# Eveltic Cookie Bundle
The Eveltic Cookie Consent Bundle is a Symfony package designed to help developers implement and manage user cookie consent on their websites, ensuring full compliance with the latest 2024 AEPD (Spanish Data Protection Agency) regulations. This bundle provides an easy-to-use interface for configuring, collecting, and storing user consent preferences, with customizable options for different types of cookies (technical, analytics, preferences, and advertisement). The package includes ready-to-use templates, supports multiple languages, and seamlessly integrates with Symfony applications, making it easier to align your website with legal requirements.

## Installation
1. Require the Bundle via Composer:
This command will download and install this bundle inside your composer folder.
```bash
composer require eveltic/cookie-bundle
```
2. Run install command
This command will create the necesary files in order to be able to adequate to your configuration needs.
```bash
php bin/console ev:cookies:install
```
3. Update the database
If you force update the database:
```bash
php bin/console doctrine:schema:update --force
```
or if you are using migrations:
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

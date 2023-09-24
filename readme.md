# Laravel CRUD Generator Package

![CRUD Example](https://your-image-url.com/crud-example.png)

This Laravel package allows you to **easily generate CRUD (Create, Read, Update, Delete) operations** for a model based on an existing database table.

## Features

- ✨ Generates CRUD operations for a given model and database table.
- 🖥️ Optional view generation with support for educational purposes (adds comments).
- 🛠️ Flexible customization options.
- 🎥 [Demo Video](your-youtube-link-here) (Replace with your YouTube link)

## Installation

You can install this package via Composer:

```bash
composer require eraufi/crud
```

Usage
To generate CRUD operations for a model, use the following command:
```bash
php artisan crud:generate {model}
```
Replace {model} with the name of the model you want to generate CRUD operations for.

Options
--generateView: Generates views for the CRUD operations. (Optional)
--isEducationl: Adds comments for educational purposes. (Optional)
Example
```bash
php artisan crud:generate Post --generateView --isEducationl
```
Requirements
Laravel >= 5.6
PHP >= 7.1
Configuration
No additional configuration is required after installing the package.

Contributing
Contributions are welcome! Please read CONTRIBUTING.md for details.

License
This package is open-sourced software licensed under the MIT license. See the LICENSE file for details.

Author
Mohammad Edris Raufi
GitHub: Your GitHub Profile
Acknowledgments
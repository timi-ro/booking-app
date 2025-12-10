# Booking Application

A booking platform connecting service agencies with customers. Agencies can list their offerings (tours, experiences, services) with rich media, while customers can browse and book.

## Overview

This application serves as a marketplace where agencies create and manage service offerings. Each offering includes details like pricing, descriptions, images, and videos to help customers make informed booking decisions.

## Key Features

- **Role-Based Access** — Three user types (Customer, Agency, Admin) with appropriate permissions
- **Offerings Management** — Agencies create, update, and delete their service listings
- **Media Uploads** — Support for images and videos attached to offerings
- **Ownership Protection** — Agencies can only modify their own offerings

## Tech Stack

- Laravel 11
- MySQL
- Laravel Sanctum (API authentication)

## Getting Started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## License

MIT

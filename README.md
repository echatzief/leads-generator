# Leads Generator Setup Guide

This guide walks you through setting up MongoDB with a Laravel project, enabling Mailchimp integration, 
and configuring a MongoDB replica set.

---

## Install MongoDB PHP Extension

### macOS

```bash
brew install shivammathur/extensions/mongodb@8.4
```

### Linux

```bash
sudo pecl install mongodb
```

---

## Start MongoDB with Docker

If you already have a local MongoDB instance, ensure the replica set is enabled. Transactions in MongoDB require a replica set.

If not, follow these steps to set up MongoDB in Docker:

### Generate the MongoDB Keyfile

```bash
openssl rand -base64 756 > ./mongodb-keyfile
```

### Set Permissions

```bash
chmod 600 ./mongodb-keyfile
```

### Initiate the Replica Set

Once MongoDB is running, connect to it and run:

```js
rs.initiate({
  _id: "rs0",
  members: [
    { _id: 0, host: "leads_generator_db:27017" }
  ]
});
```

### Spin Up Docker Containers

```bash
docker-compose up -d --build
```

---

## Configure Environment Variables

Update your `.env` file with MongoDB credentials:

```ini
DB_CONNECTION=mongodb
DB_HOST=leads_generator_db
DB_PORT=27017
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## Mailchimp Setup

1. Create a Mailchimp account.
2. Update your `.env` file with the following:

```ini
MAILCHIMP_API_KEY=your_mailchimp_api_key
MAILCHIMP_SERVER=your_mailchimp_server_prefix
```

---

## Run the Development Server

```bash
npm run dev
php artisan serve
```

## Run the backend tests
```bash
./vendor/bin/pest tests/Feature/MailchimpTest.php
./vendor/bin/pest tests/Feature/LeadsControllerTest.php
```

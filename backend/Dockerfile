FROM dunglas/frankenphp:1.3-php8.3

# Install unzip and necessary PHP extensions
RUN apt-get update && apt-get install -y unzip \
    && install-php-extensions pdo_pgsql redis

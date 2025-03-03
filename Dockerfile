FROM php:7.4-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
  build-essential \
  libpng-dev \
  libjpeg62-turbo-dev \
  libfreetype6-dev \
  locales \
  libzip-dev \
  zip \
  jpegoptim optipng pngquant gifsicle \
  vim \
  unzip \
  git \
  libpq-dev \
  procps \
  cron \
  sudo \
  curl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo pdo_pgsql zip exif pcntl
RUN docker-php-ext-install gd

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# auto script
COPY phprun.sh /usr/local/bin/phprun.sh
RUN chmod +x /usr/local/bin/phprun.sh

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www
RUN usermod -aG sudo www
RUN echo "www     ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www:www . /var/www

# RUN crontab -u www -e

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000

CMD ["phprun.sh"]

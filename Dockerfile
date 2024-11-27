# Étape 1 : Utiliser une image de base PHP avec FPM (FastCGI Process Manager)
FROM php:8.2-fpm

# Étape 2 : Installer les dépendances nécessaires pour les extensions PHP et autres outils
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    zlib1g-dev \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql intl opcache

# Étape 3 : Installer Composer (le gestionnaire de dépendances PHP)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Étape 4 : Définir le répertoire de travail dans le conteneur
WORKDIR /var/www/html

# Étape 5 : Copier les fichiers du projet dans le conteneur
COPY . /var/www/html

# Étape 6 : Installer les dépendances PHP via Composer
RUN composer install --no-scripts --no-interaction

# Étape 7 : Exposer le port 9000 (pour que PHP-FPM fonctionne)
EXPOSE 9000

# Étape 8 : Lancer PHP-FPM
CMD ["php-fpm"]

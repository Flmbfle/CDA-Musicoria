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
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Étape 3 : Installer Composer (le gestionnaire de dépendances PHP)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Étape 4 : Installer wait-for-it (pour attendre que la base de données soit prête)
RUN curl -sS https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh -o /usr/local/bin/wait-for-it && chmod +x /usr/local/bin/wait-for-it

# Étape 5 : Définir le répertoire de travail dans le conteneur
WORKDIR /var/www/html

# Étape 6 : Copier les fichiers du projet dans le conteneur
COPY . /var/www/html

# Étape 7 : Installer les dépendances PHP via Composer
RUN composer install --no-scripts --no-interaction

# Étape 8 : Exposer le port 9000 (pour que PHP-FPM fonctionne)
EXPOSE 9000


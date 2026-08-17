FROM ubuntu:24.04

LABEL maintainer="Alba Tec Production"

ARG WWWGROUP=1000
ARG WWWUSER=1000

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Configurar repositório PHP
RUN mkdir -p /etc/apt/keyrings \
    && apt-get update \
    && apt-get install -y gnupg curl ca-certificates \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list

# Instalar libxslt1-dev separadamente (Recomendado)
RUN apt-get update && \
    apt-get install -y libxslt1-dev

# Instalar pacotes PHP e dependências
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-xml \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-gd \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-soap \
    php8.2-redis \
    php8.2-memcached \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Criar usuário e grupo
RUN groupadd --force -g $WWWGROUP www-data \
    && useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u $WWWUSER www-data

# Copiar arquivos da aplicação
COPY --chown=www-data:www-data . /var/www/html

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Instalar dependências do Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader --no-interaction

# Expor portas
EXPOSE 80

# Comando de inicialização
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]

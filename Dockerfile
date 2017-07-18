FROM php:7.0-apache

RUN echo 'deb http://s3-eu-west-1.amazonaws.com/qafoo-profiler/packages debian main' > /etc/apt/sources.list.d/tideways.list && \
    curl -sS 'https://s3-eu-west-1.amazonaws.com/qafoo-profiler/packages/EEB5E8F4.gpg' | apt-key add - && \
    apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get -yq install tideways-php graphviz && \
    apt-get autoremove --assume-yes && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    echo 'extension=tideways.so\ntideways.auto_prepend_library=0\ntideways.output_dir=/traces' > /usr/local/etc/php/conf.d/tideways.ini

COPY ./html /var/www/html/
COPY ./lib /var/www/lib/

#Set www-data uid/gid to the host owner uid/gid (generally 1000:1000)
RUN usermod -u 1000 www-data
RUN groupmod -g 1000 www-data

VOLUME ["/var/www/traces"]



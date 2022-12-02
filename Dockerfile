FROM php:7.0-apache

#Install Graphviz (dot binary is used to display graphs)
# and build extension essentials
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get -yq install graphviz git && \
    apt-get autoremove --assume-yes && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN git clone https://github.com/tideways/php-xhprof-extension.git && \
  ( \
    cd php-xhprof-extension && \
    phpize && \
    ./configure && \
    make -j$(nproc) && \
    make install \
  ) && \
  docker-php-ext-enable tideways_xhprof

#Set www-data uid/gid to the host owner uid/gid (generally 1000:1000)
RUN usermod -u 1000 www-data
RUN groupmod -g 1000 www-data

#Setup ENV variables
ENV PROFILER_PATH='/traces'
ENV PROFILER_SUFFIX='xhprof'

#Copy app in container
COPY ./html /var/www/html/
COPY ./lib /var/www/lib/

#This is where you should link your XHProf traces
VOLUME ["/traces"]

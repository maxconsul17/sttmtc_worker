# Use the base image from the Docker repository
FROM whoamiken/ci3:latest

# Copy your application code to the container
COPY . /var/www/html/hris

# Set permissions
RUN chmod -R 777 /tmp
RUN chown -R www-data:www-data /tmp

# Install cron and curl
RUN apt-get update && apt-get install -y cron curl

# Create a script to run the curl command
RUN echo '#!/bin/bash' > /usr/local/bin/curl_localhost.sh && \
    echo 'while true; do curl -s http://localhost > /dev/null 2>&1; sleep 1; done' >> /usr/local/bin/curl_localhost.sh && \
    chmod +x /usr/local/bin/curl_localhost.sh

# Set up the cron job
RUN echo '* * * * * root for i in {0..2}; do /usr/local/bin/curl_localhost.sh; sleep 2; done' > /etc/cron.d/curl_cron && \
    chmod 0644 /etc/cron.d/curl_cron

# Ensure cron job is running
RUN crontab /etc/cron.d/curl_cron

# Create an entrypoint script to run both cron and apache2
RUN echo '#!/bin/bash' > /entrypoint.sh && \
    echo 'service cron start' >> /entrypoint.sh && \
    echo 'apache2-foreground' >> /entrypoint.sh && \
    chmod +x /entrypoint.sh

# Set the entrypoint
ENTRYPOINT ["/entrypoint.sh"]
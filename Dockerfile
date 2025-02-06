# Use the base image from the Docker repository
FROM whoamiken/ci3:latest

# Copy your application code to the container
COPY . /var/www/html/hris

# Set permissions
RUN chmod -R 777 /tmp
RUN chown -R www-data:www-data /tmp

# Install cron and curl
RUN apt-get update && apt-get install -y cron curl unzip

# Create a script to run the PHP worker once
# RUN echo '#!/bin/bash' > /usr/local/bin/run_worker.sh && \
#     echo 'php /var/www/html/hris/index.php worker/listen > /dev/null 2>&1' >> /usr/local/bin/run_worker.sh && \
#     chmod +x /usr/local/bin/run_worker.sh

# Set up a cron job to run the script at system boot
RUN echo '@reboot root /usr/local/bin/run_worker.sh' > /etc/cron.d/worker_cron && \
    chmod 0644 /etc/cron.d/worker_cron

# Ensure cron job is running
RUN crontab /etc/cron.d/worker_cron

# Create an entrypoint script to run both cron and apache2
RUN echo '#!/bin/bash' > /entrypoint.sh && \
    echo 'service cron start' >> /entrypoint.sh && \
    # echo 'php /var/www/html/hris/index.php worker/listen' >> /entrypoint.sh && \
    chmod +x /entrypoint.sh

# Set the entrypoint
ENTRYPOINT ["/entrypoint.sh"]

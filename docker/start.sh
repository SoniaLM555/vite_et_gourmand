#!/bin/sh
php bin/console cache:clear --env=prod
apache2-foreground

#!/usr/bin/env bash

find . -name '*.php' | while IFS=$'\n' read -r FILE; do
  echo $(php -w $FILE) > $FILE
done

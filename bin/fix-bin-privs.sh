#!/bin/sh

# find all bin/ folders inside home dir and make executable all files there
find $HOME/ -type d -name bin | while read bin ; do
    find "$bin" -type f -or -type l | xargs chmod -v +x
done

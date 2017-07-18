#!/bin/bash

cd $(dirname "$0")

docker build -t jeromebreton/xhprof-simple-gui:source .
docker run --rm -p 3731:80 --name jeromebreton-xhprof-simple-gui -v "`pwd`/traces":/traces jeromebreton/xhprof-simple-gui:source

cd -

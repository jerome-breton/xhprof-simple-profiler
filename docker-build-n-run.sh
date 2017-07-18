#!/bin/bash

cd $(dirname "$0")

docker build -t jeromebreton/xhprof-simple-viewer:source .
docker run --rm -p 3731:80 --name jeromebreton-xhprof-simple-viewer -v "`pwd`/traces":/traces jeromebreton/xhprof-simple-viewer:source

cd -

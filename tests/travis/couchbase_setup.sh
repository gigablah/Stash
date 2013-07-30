#!/bin/sh

set -e

echo "****************************"
echo "Installing couchbase server."
echo "****************************"
echo ""
echo ""
echo "Installing libcouchbase..."
sudo wget -O/etc/apt/sources.list.d/couchbase.list http://packages.couchbase.com/ubuntu/couchbase-ubuntu1204.list
sudo wget http://packages.couchbase.com/ubuntu/couchbase.key && sudo cat couchbase.key | sudo apt-key add -
sudo apt-get update
sudo apt-get install libcouchbase2 libcouchbase-dev
echo "Installing couchbase-server..."
sudo wget http://packages.couchbase.com/releases/2.0.1/couchbase-server-enterprise_x86_64_2.0.1.deb
sudo dpkg -i couchbase-server-enterprise_x86_64_2.0.1.deb
sudo service couchbase-server start
echo "Creating bucket..."
/opt/couchbase/bin/couchbase-cli cluster-init -c 127.0.0.1:8091 --cluster-init-username=Administrator --cluster-init-password=password --cluster-init-ramsize=256
curl -X POST -u Administrator:password -d authType=none -d proxyPort=11212 -d bucketType=couchbase -d name=test -d flushEnabled=1 -d ramQuotaMB=100 -d replicaNumber=0 http://127.0.0.1:8091/pools/default/buckets
# /opt/couchbase/bin/couchbase-cli bucket-create -c 127.0.0.1:8091 --bucket=test --bucket-type=couchbase --bucket-ramsize=100 --bucket-replica=0 --enable-flush=1 -u Administrator -p password
echo "Finished installing couchbase server."
echo ""
echo ""
echo "*******************************"
echo "Installing couchbase extension."
echo "*******************************"
echo ""
echo ""
echo "Downloading..."
git clone git://github.com/gigablah/php-ext-couchbase.git
echo "Configuring..."
cd php-ext-couchbase
phpize
./configure
echo "Installing..."
make
sudo make install
cd ..
echo "Finished installing couchbase extension."

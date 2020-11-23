#!/bin/bash

git config pull.rebase false
git config user.name "p1ratrulezzz"
git config user.email "git@p1ratrulezzz.me"
KEY_PATH=$(pwd -P)"/deployment-private.key"
KEY_PATH_PUB=$(pwd -P)"/deployment-public.key"
echo "${SSH_PUBKEY}" > "${KEY_PATH_PUB}"
echo "Adding an SSH key to agent"
eval $(ssh-agent -s)
touch "${KEY_PATH}"
chmod 0600 "${KEY_PATH}"
echo "${SSH_PRIVATEKEY}" > "${KEY_PATH}"
ssh-add -t 3600 "${KEY_PATH}"
echo "Checkout master"
git fetch origin
git checkout -b master origin/master
git pull origin master
echo "Updating the og id"
php replace_og_id.php
echo "Add to git"
git add index.html
git commit -m"[skip-ci] OG ID CI update"
git push -f origin master
rm -f "${KEY_PATH_PUB}"
rm -f "${KEY_PATH_PUB}"

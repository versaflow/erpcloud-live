#!/bin/bash
#Borrowed from anacron
#SHELL=/bin/sh
#PATH=/usr/local/bin:/usr/bin:/bin
#End borrowed from anacron
#PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

cd /home/erpcloud-helpdesk/htdocs/
git add .
git commit -am "automated $(date +"%Y-%m-%d")"
git push --set-upstream origin master

cd /home/erpcloud-live/erpcloud-live/
git add .
git commit -am "automated $(date +"%Y-%m-%d")"
git push --set-upstream origin master




# git merge origin/master
# rm -rf .git
# git init
    # $username = 'versaflow';
    # $accessToken = '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';
    # git push --set-upstream erpcloud master
    # git config –global user. password “YourPassword”
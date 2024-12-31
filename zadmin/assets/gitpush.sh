#!/bin/bash
#Borrowed from anacron
#SHELL=/bin/sh
#PATH=/usr/local/bin:/usr/bin:/bin
#End borrowed from anacron
#PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin



# cd /home/erpcloud-live/htdocs
# git add .
# git commit -am "automated $(date +"%Y-%m-%d")"
# git push erpcloud

cd /home/erpcloud-live/htdocs/
git add .
git commit -am "automated $(date +"%Y-%m-%d")"
git push

cd /home/erpcloud-helpdesk/htdocs/
git add .
git commit -am "automated $(date +"%Y-%m-%d")"
git push





# git merge origin/master
# rm -rf .git
# git init
    # $username = 'versaflow';
    # $accessToken = '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';
    # git push --set-upstream erpcloud master
    # git config –global user. password “YourPassword”
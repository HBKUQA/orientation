**Git global setup**
```
Add your ssh public key
https://bitbucket.org/account/settings/ssh-keys/
```


**Create a new repository**
```
git clone git@bitbucket.org:baach/hbku-soos.git
cd tfh
touch README.md
git add README.md
git commit -m "add README"
git push -u origin master
```

**Push an existing folder**
```
cd existing_folder
git init
git remote add origin git@bitbucket.org:baach/hbku-soos.git
git add .
git commit -m "Initial commit"
git push -u origin master
```


**Push an existing Git repository**
```
cd existing_repo
git remote rename origin old-origin
git remote add origin git@bitbucket.org:baach/hbku-soos.git
git push -u origin --all
git push -u origin --tags
```

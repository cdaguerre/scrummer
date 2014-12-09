scrummer
========

An app to handle Github, Trello and Capistrano webhooks in a Scrum workflow.

1. Get your Trello API key and secret: https://trello.com/1/appKey/generate
2. Request a token: https://trello.com/1/authorize?response_type=token&key=[your-api-key]&scope=read,write&expiration=never&name=GithubSync

#### Creating your app on Heroku
This assumes you have the Heroku toolbelt installed and are logged in.

1. Clone this repository 
   
  ```bash
  $ git clone git@github.com:cdaguerre/scrummer.git
  ```

2. Create the app on heroku

  ```bash
  $ heroku create
  ```

3. Set your Trello and Github credentials as env vars:
  
  ```bash
  $ heroku config:set \
    \GITHUB_USER={your-github-username}
    \GITHUB_PASSWORD={your-github-password}
    \GITHUB_ORGANIZATION={username-or-organization-owning-repository}
    \GITHUB_REPOSITORY={repository-name}
    \TRELLO_API_KEY={your-trello-api-key}
    \TRELLO_SECRET={your-trello-secret}
    \TRELLO_TOKEN={your-trello-token}
    \TRELLO_BOARD_ID={your-trello-board-id}
  ```

4. Deploy the app to heroku
  
  ```bash
  $ mv config.yml.dist config.yml
  $ git add -A
  $ git commit -m 'set up'
  $ git push heroku master
  ```
5. Check your app is up 
  
  ```bash
  $ heroku ps
  ````
  You should see something like the following:
  ```bash
  === web (1X): `vendor/bin/heroku-php-apache2 web/`
  web.1: up 2014/12/09 18:54:11 (~ 4s ago)
  ```

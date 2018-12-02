# Google Trends Slackbot
Hackathon Demo in UBC Local Hack Day 2018

A Slackbot that picks a Google Trends item and tells the summary of the top three. 

<img alt="Frightgeist 2018 Rankings for Halloween Costumes" src="https://kelvinau.github.io/Google-Trends-Slackbot/Frightgeist%202018%20Rankings%20for%20Halloween%20Costumes.png" width="100%"/>
<img alt="MLB Playoofs Most searched players" src="https://kelvinau.github.io/Google-Trends-Slackbot/MLB%20Playoofs%20Most%20searched%20players.png" width="100%"/>

## Setup
- Upload this repository to the Slack Request URL location, and add `config.ini` for `BOT_TOKEN`, `DB_SERVER`, `DB_ADMIN`, `DB_PASSWORD`, 
`DB_NAME`, and `DB_TABLE`
- Setup bot (https://api.slack.com/bot-users) and enable `app_mention` in `Event Subscriptions`
- DB Table - CREATE TABLE `{DB_TABLE}` ( channel VARCHAR(256), user VARCHAR(256), conversation LONGTEXT, primary key (channel, user) );

## How to use
Mention your bot and type top trend e.g.`@bot top trend`

## What I learnt
**Relying on Slackbot for the GUI is the biggest mistake**
- Multi-line message is still not supported as of December 01, 2018 
(earliest request started in 2015 https://twitter.com/slackhq/status/592923143309787136?lang=en)
- No custom styling for the option dropdown in interactive messages. If an item is too long, it is trimmed with '...'
- The request structures from Slack to your own server can be quite different, 
e.g. normal message (`application/json`) vs interactive messages (`application/x-www-form-urlencoded` with a key `payload`).

# Twitter-TwitterBot
A PHP Twitter bot

Some of the code behind my PHP Twitter bot, which can be found in action at twitter.com/peterardennet

My Twitter bot makes use of the "TwitterAPIExchange" Github project.

The code in this class is can be used to Retweet and/or Like Tweets based on a list of interests passed to the method, Favourite
any Tweets sent to the bot, reply to some Tweets sent to the bot in certain cases, Tweet a NASA Image Of The Day post with an image
and Tweet a local Weather forecast. (Additional APIs are required for NASA and OpenWeatherMap.)

I use a Raspberry Pi and Cron jobs to schedule these tasks to run at certain times throughout the day.

I use MySQL to store information such as the "interests" that are passed to the bot.

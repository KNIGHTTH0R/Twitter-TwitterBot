<?php

class TwitterBot
{
	const OAUTH_ACCESS_TOKEN = "TwitterAccessToken";
	const OAUTH_ACCESS_TOKEN_SECRET = "TwitterAccessTokenSecret";
	const CONSUMER_KEY = "TwitterConsumerKey";
	const CONSUMER_SECRET = "TwitterConsumerSecret";
	
	//Pass in a string array of "interests" that the bot will search for and Retweet
	function RetweetInterests($searchWords)
	{
		$settings = array
		(
			'oauth_access_token' => self::OAUTH_ACCESS_TOKEN,
			'oauth_access_token_secret' => self::OAUTH_ACCESS_TOKEN_SECRET,
			'consumer_key' => self::CONSUMER_KEY,
			'consumer_secret' => self::CONSUMER_SECRET
		);
			
		//The big foreach loop now to Retween a number of Tweets for each subject in the passed-in string array
		foreach($searchWords as $searchWord) 
		{
			$url = 'https://api.twitter.com/1.1/search/tweets.json';
			
			$getfield = "q=$searchWord";
			$requestMethod = 'GET';
			$twitter = new TwitterAPIExchange($settings);
			$fullResponse = $twitter->setGetfield($getfield)
							->buildOauth($url, $requestMethod)
							->performRequest();
							
			$json = json_decode($fullResponse);
			
			//Retweet $numberOfTweetsToRetweet tweets about each subject
			$numberOfTweetsToRetweet = 2;
			for($count = 0; $count < $numberOfTweetsToRetweet; $count++)
			{
				$tweetId = $json->statuses[$count]->id_str;			
				
				$url = "https://api.twitter.com/1.1/statuses/retweet/$tweetId.json";
				$requestMethod = 'POST';
									
				$postfields = array(
				  'trim_user' => "1"
				);

				$twitter = new TwitterAPIExchange($settings);
				$twitter->buildOauth($url, $requestMethod)
						->setPostfields($postfields)
						->performRequest();				
			}
		}
		
		return "Sent some tweets...";
	}

	//Pass in a string array of "interests" that the bot will search for and Like
	function LikeTweets($searchWords)
	{
		$settings = array
		(
			'oauth_access_token' => self::OAUTH_ACCESS_TOKEN,
			'oauth_access_token_secret' => self::OAUTH_ACCESS_TOKEN_SECRET,
			'consumer_key' => self::CONSUMER_KEY,
			'consumer_secret' => self::CONSUMER_SECRET
		);
		
		//The big foreach loop now to Like a number of Tweets for each subject in the passed-in string array
		foreach($searchWords as $searchWord) 
		{
			$url = 'https://api.twitter.com/1.1/search/tweets.json';
			
			$getfield = "q=$searchWord";
			$requestMethod = 'GET';
			$twitter = new TwitterAPIExchange($settings);
			$fullResponse = $twitter->setGetfield($getfield)
							->buildOauth($url, $requestMethod)
							->performRequest();
							
			$json = json_decode($fullResponse);
		
			//Like $numberOfTweetsToLike tweets about each subject?
			$numberOfTweetsToLike = 2;

			for($count = 0; $count < $numberOfTweetsToLike; $count++)
			{
				$tweetID = $json->statuses[$count]->id_str;
				
				//Let's favourite!
				// $url = 'https://api.twitter.com/1.1/favorites/create.json';
				// $url = "https://api.twitter.com/1.1/favorites/create.json?id=$tweetID";
				$url = "https://api.twitter.com/1.1/favorites/create.json";
				
				$postfields = array (
					'id' => "$tweetID"
				);
				
				$requestMethod = 'POST';
				
				$twitter = new TwitterAPIExchange($settings);
				$temp = $twitter->buildOauth($url, $requestMethod)
								->setPostfields($postfields)
								->performRequest();
			}
		}
		
		echo "Favourited some Tweets...";
	}
	
	//Likes any Tweets send to the Twitter bot (and in some cases, replies to the Tweets)
	//You need to pass in the ID of a Tweet sent to the bot so you can check if any more Tweets have been sent to it SINCE that one
	//The method returns the new latest Tweet ID, ready to be saved so it can be passed back in next time
	function ManageMentions($oldestMentionID)
	{
		$settings = array
		(
			'oauth_access_token' => self::OAUTH_ACCESS_TOKEN,
			'oauth_access_token_secret' => self::OAUTH_ACCESS_TOKEN_SECRET,
			'consumer_key' => self::CONSUMER_KEY,
			'consumer_secret' => self::CONSUMER_SECRET
		);

		//id_str was used to Retweet, so let's go with that!
		
		$url = 'https://api.twitter.com/1.1/statuses/mentions_timeline.json';
		$getfield = "since_id=$oldestMentionID";

		$requestMethod = 'GET';

		$twitter = new TwitterAPIExchange($settings);
		
		$fullResponse = $twitter->setGetfield($getfield)
						->buildOauth($url, $requestMethod)
						->performRequest();
				
		$json = json_decode($fullResponse);

		$mentionCount = count($json);
		
		//If there are no mentions, no need to go any further!
		if($mentionCount == 0)
		{
			return 0;
		}
			
		//The "since_id" for next time will be the mention in $json[0] this time
		$newSinceID = $json[0]->id_str;
				
		//for each mention, Favourite that Tweet!	
		for($tweetIndex = 0; $tweetIndex < $mentionCount; $tweetIndex++)
		{
			$tweetID = $json[$tweetIndex]->id_str;
			
			//Let's favourite!
			$url = 'https://api.twitter.com/1.1/favorites/create.json';
			
			$postfields = array (
				'id' => "$tweetID"
			);
			
			$requestMethod = 'POST';
			
			$twitter = new TwitterAPIExchange($settings);
			$temp = $twitter->buildOauth($url, $requestMethod)
							->setPostfields($postfields)
							->performRequest();
							
			self::ReplyToTweet($json[$tweetIndex]);
		}
		
		return $newSinceID;

	}
	
	//Reply to Tweets sent to the bot in certain cases...
	function ReplyToTweet($tweetJSON)
	{
		$settings = array
		(
			'oauth_access_token' => self::OAUTH_ACCESS_TOKEN,
			'oauth_access_token_secret' => self::OAUTH_ACCESS_TOKEN_SECRET,
			'consumer_key' => self::CONSUMER_KEY,
			'consumer_secret' => self::CONSUMER_SECRET
		);
		
		$url = 'https://api.twitter.com/1.1/statuses/update.json';

		$screenName = $tweetJSON->user->screen_name;
		$mentionText = strtoupper($tweetJSON->text);
		
		$status = "";
		
		//Only reply in certain conditions
		//Most Tweets to the bot are:
		/*
		 - Thanks for the Retweet
		 - Thanks for the Follow
		 - Thanks for the RT
		 - Or a combination of all of these
		*/
		if((strpos($mentionText, "THANKS") !== false
		|| strpos($mentionText, "THANK YOU") !== false
		|| strpos($mentionText, "THANKYOU") !== false
		|| strpos($mentionText, "THX") !== false)
		&& (strpos($mentionText, "FOR") !== false
		|| strpos($mentionText, "TO") !== false))
		{		
			if(strpos($mentionText, "RETWEET") !== false
			|| strpos($mentionText, " RT") !== false)
			{
				$status = "No problem @$screenName :)";
			}
			else if(strpos($mentionText, "FOLLOW") !== false
			|| strpos($mentionText, "FOLLOWING") !== false
			|| strpos($mentionText, "FOLLOWERS") !== false)
			{
				$status = "Good to connect @$screenName :)";
			}
			else if(strpos($mentionText, "LIKES") !== false
			|| strpos($mentionText, "LIKE") !== false
			|| strpos($mentionText, "LIKING") !== false)
			{
				$status = "No worries @$screenName :)";
			}
			else
			{
				$status = "Thanks @$screenName :)";
			}
			
			$inReplyTo = $tweetJSON->id;
				
			$postfields = array(
			  'status' => "$status",
			  'in_reply_to_status_id' => "$inReplyTo"
			);	
			
			$requestMethod = 'POST';
			$twitter = new TwitterAPIExchange($settings);
			$fullResponse = $twitter->buildOauth($url, $requestMethod)
							->setPostfields($postfields)
							->performRequest();	
		}
		else
		{
			return;
		}
	}

	//Tweet the NASA Image Of The Day
	//Pass in the 'NASA Image Of The Day' JSON, obtained from the NASA API
	function TweetNASAIOTD($nasaJSON)
	{	
		//Harvest NASA IOTD properties
		$copyright = $nasaJSON->copyright;
		$date = $nasaJSON->date;
		$explanation = $nasaJSON->explanation;
		$title = $nasaJSON->title;
		$image = $nasaJSON->url;
				
		$settings = array
		(
			'oauth_access_token' => self::OAUTH_ACCESS_TOKEN,
			'oauth_access_token_secret' => self::OAUTH_ACCESS_TOKEN_SECRET,
			'consumer_key' => self::CONSUMER_KEY,
			'consumer_secret' => self::CONSUMER_SECRET
		);

		//Image path
		$path = $nasaJSON->url;

		//Need to do the INIT first to get a Media ID
		//https://dev.twitter.com/rest/reference/post/media/upload-init
		// $url = "https://api.twitter.com/1.1/statuses/retweet/$tweetId.json";
		$url = 'https://upload.twitter.com/1.1/media/upload.json';
		$requestMethod = 'POST';

		$postfields = array(
			'media_type' => "image/jpeg",
			'command' => "INIT",
			'total_bytes' => strlen(file_get_contents($path))
		);

		$twitter = new TwitterAPIExchange($settings);
		$fullResponse = $twitter->buildOauth($url, $requestMethod)
				->setPostfields($postfields)
				->performRequest();

		$json = json_decode($fullResponse);

		$mediaID = $json->media_id_string;

		//Next need to convert the media data
		//http://stackoverflow.com/questions/3967515/how-to-convert-image-to-base64-encoding
		//$path = "http://openweathermap.org/img/w/10n.png";
		$type = "jpg";
		$data = file_get_contents($path);
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

		$mediaData = base64_encode($data);

		//Need to do the APPEND next to send the data. Sort of
		//https://upload.twitter.com/1.1/media/upload.json?command=APPEND&media_id=123&segment_index=2&media_data=123
		$url = 'https://upload.twitter.com/1.1/media/upload.json';

		$requestMethod = 'POST';

		$postfields = array(
			'command' => "APPEND",
			'media_id' => "$mediaID",
			'segment_index' => "0",
			'media_data' => "$mediaData"
		);

		$twitter = new TwitterAPIExchange($settings);
		$fullResponse = $twitter->buildOauth($url, $requestMethod)
				->setPostfields($postfields)
				->performRequest();

		//Lastly, need to FINALIZE
		//https://dev.twitter.com/rest/reference/post/media/upload-finalize
		//https://upload.twitter.com/1.1/media/upload.json?command=FINALIZE&media_id=710511363345354753
		$url = 'https://upload.twitter.com/1.1/media/upload.json';

		$requestMethod = 'POST';

		$postfields = array(
			'command' => "FINALIZE",
			'media_id' => "$mediaID",
		);

		$twitter = new TwitterAPIExchange($settings);
		$fullResponse = $twitter->buildOauth($url, $requestMethod)
				->setPostfields($postfields)
				->performRequest();

		//Finally, Tweet the image-to-base64-encoding
		$url = 'https://api.twitter.com/1.1/statuses/update.json';

		$output = "$title\r#NASA #ImageOfTheDay #Space";

		$postfields = array(
		  'status' => "$output",
		  'media_ids' => "$mediaID"
		);	

		$requestMethod = 'POST';
		$twitter = new TwitterAPIExchange($settings);
		$fullResponse = $twitter->buildOauth($url, $requestMethod)
						->setPostfields($postfields)
						->performRequest();

		//echo $fullResponse;
		echo "Tweeted NASA IOTD...";
	}

	//Tweet a Weather forecast
	//Weather forecast obtained using the OpenWeatherMap API
	function TweetWeather()
	{
		$apiKey = "OpenWeatherAPIKey";
		$location = "Liverpool,uk";
			
		//http://www.openweathermap.org
		$url = "http://api.openweathermap.org/data/2.5/weather?q=$location&appid=$apiKey&units=metric";

		$response = file_get_contents($url);

		$json = json_decode($response);

		//Temperature: Celsius
		//Wind Speed: metres/second
		$weatherMain = $json->weather[0]->main;
		$weatherDescription = $json->weather[0]->description;
		$tempNow = round($json->main->temp);
		$tempMin = round($json->main->temp_min);
		$tempMax = round($json->main->temp_max);
		$windSpeedMPS = $json->wind->speed;

		$windSpeedMPH = round(2.237 * $windSpeedMPS);

		$stringReplacement = "#Liverpool #Weather:\r%s, %s.\r%dC.\rMinimum Temperature: %dC\rMaximum Temperature: %dC.\rWind Speed: %gmph";

		$output = sprintf($stringReplacement, $weatherMain, $weatherDescription, $tempNow, $tempMin, $tempMax, $windSpeedMPH);
		
		//Some alternative formats in case any of the "description" fields are particularly long
		$maxTweetLength = 140;
		
		$tweetLength = strlen($output);
		
		if($tweetLength > $maxTweetLength){	
			$stringReplacement = "#Liverpool #Weather:\r%s, %s.\r%dC.\rMin Temperature: %dC\rMax Temperature: %dC.\rWind Speed: %gmph";
			$output = sprintf($stringReplacement, $weatherMain, $weatherDescription, $tempNow, $tempMin, $tempMax, $windSpeedMPH);
		}
		
		$tweetLength = strlen($output);
		
		if($tweetLength > $maxTweetLength){	
			$stringReplacement = "#Liverpool #Weather:\r%s, %s.\r%dC.\rMin Temp: %dC\rMax Temp: %dC.\rWind Speed: %gmph";
			$output = sprintf($stringReplacement, $weatherMain, $weatherDescription, $tempNow, $tempMin, $tempMax, $windSpeedMPH);
		}
		
		$tweetLength = strlen($output);
		
		if($tweetLength > $maxTweetLength){	
			$stringReplacement = "#Weather:\r%s, %s.\r%dC.\rMin Temp: %dC\rMax Temp: %dC.\rWind Speed: %gmph";
			$output = sprintf($stringReplacement, $weatherMain, $weatherDescription, $tempNow, $tempMin, $tempMax, $windSpeedMPH);
		}
		
		$tweetLength = strlen($output);
		
		if($tweetLength > $maxTweetLength){	
			$stringReplacement = "#Weather:\r%s, %s.\r%dC.\rMin Temp: %dC\rMax Temp: %dC.\rWind Spd: %gmph";
			$output = sprintf($stringReplacement, $weatherMain, $weatherDescription, $tempNow, $tempMin, $tempMax, $windSpeedMPH);
		}
		
		$tweetLength = strlen($output);
		
		if($tweetLength > $maxTweetLength){	
			//Well, we tried! The overall format of the Tweet will need adjusting - this weather forecast is just too long!
			echo $output;
			return;
		}
		
		//https://api.twitter.com/1.1/statuses/update.json&display_coordinates=false
		$settings = array
		(
			'oauth_access_token' => self::OAUTH_ACCESS_TOKEN,
			'oauth_access_token_secret' => self::OAUTH_ACCESS_TOKEN_SECRET,
			'consumer_key' => self::CONSUMER_KEY,
			'consumer_secret' => self::CONSUMER_SECRET
		);
		
		$url = 'https://api.twitter.com/1.1/statuses/update.json';

		$postfields = array(
		  'status' => "$output"
		);	
		
		$requestMethod = 'POST';
		$twitter = new TwitterAPIExchange($settings);
		$fullResponse = $twitter->buildOauth($url, $requestMethod)
						->setPostfields($postfields)
						->performRequest();

		echo $fullResponse;		
	}
}
?>
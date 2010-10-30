<?
$dirs = DOC_INC."libraries/zend-gdata";
ini_set("include_path", ini_get('include_path').":{$dirs}");
require_once DOC_INC.'libraries/zend-gdata/Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_App_Exception');

class Search extends NonArchivedTable
{	
	public function __construct($id_search)
	{
		parent::__construct('search', $id_search);
	}
	
	public static function create()
	{
		$query = "INSERT INTO `search` (`active`, `created`) VALUES ('1', NOW())";
		$res = self::query($query);
		
		$query = "SELECT MAX(`id_search`) AS `val` FROM `search`";
		$res = self::query($query);
		
		if(mysql_num_rows($res) == '1')
		{
			$res = mysql_fetch_assoc($res);
			return new Search($res['val']);
		}
		return false;
	}
	
	public function execute()
	{
		$this->setLastExecuted(date('Y-m-d H:i:s'));
		$this->executeTwitterSearch();
		//$this->executeYouTubeSearch();
		$tw=new Service('1');
		
		if (!$tw->isDown())
		{
			$this->executeTwitterSearch();
		}
		else
		{
			echo "Skipping Twitter-API is down \n";
		}
		
//		
		
	}
	
	
	public function executeTwitterSearch()
	{
		global $positive;
		global $negative; 
		
		$headers = array('X-Twitter-Client: ', 'X-Twitter-Client-Version: ', 'X-Twitter-Client-URL: ');
		$url = 'http://search.twitter.com/search.json?q='.urlencode($this->getQuery());
		if($this->getMaxThreadTwitterId())
		{
			$url .= '&since_id='.urlencode($this->getMaxThreadTwitterId());
		}
		if($this->getGeocode())
		{
			$url .= '&geocode='.urlencode($this->getGeocode());
		}
		
		$ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, '');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);
		
		$response = json_decode($response);
			
		if($response->results)
		{
			foreach($response->results as $res)
			{
				//If it isn't in the database AND if there is a filter it matches or there is no filter
				if(!$this->twitterMessageExists($res->id) && ($this->getFilter() == '' || stristr($res->text, $this->getFilter())))
		try{	
				if($response->results)
				{
					$twitter_message = TwitterMessage::create();
					
					$twitter_message->setSearchId($this->getId());
					$twitter_message->setTwitterMessageId($res->id);
					if(!($profile = TwitterProfile::getTwitterProfileFromTwitterId($res->from_user_id)))
					foreach($response->results as $res)
					{
						echo 'Create new TwitterProfile '.$res->from_user."\n";
						$profile = TwitterProfile::create();
					}
					$profile->setTwitterId($res->from_user_id);
					$profile->setScreenName($res->from_user);
					$profile->setProfileImageURL($res->profile_image_url);
					$twitter_message->setTwitterProfileFrom($profile);
					
					if(!($profile = TwitterProfile::getTwitterProfileFromTwitterId($res->to_user_id)))
					{
						echo 'Create new TwitterProfile '.$res->to_user."\n";
						$profile = TwitterProfile::create();
					}
					$profile->setTwitterId($res->to_user_id);
					$profile->setScreenName($res->screen_name);
					$twitter_message->setTwitterProfileTo($profile);
					
					$twitter_message->setText($res->text);
					$twitter_message->setCreatedAt(date('Y-m-d H:i:s', strtotime($res->created_at)));
					$twitter_message->setISOLanguageCode($res->iso_language_code);
					$twitter_message->setSource($res->source);
					
					$twitter_message->setSentiment(0);
					//$twitter_message->setProfileImageURL($res->profile_image_url);
					
					//If the tweet has geo set, try and use it
					if(!is_null($res->geo))
					{
						$twitter_message->setLatitude($res->geo->coordinates[0]);
						$twitter_message->setLongitude($res->geo->coordinates[1]);
						$twitter_message->setPrecision('twitter_coord');
					}
					else
					{
						//$twitter_message->setLatLongFromLocation($res->location);
					}
					$twitter_message->setGeo(serialize($res->geo));
					$twitter_message->setLocation($res->location);
					$twitter_message->setLocationSerial(serialize($res->location));
					
					foreach($positive as $pos)
					{
						if(stripos($res->text, $pos))
						{
							$twitter_message->incrimentSentiment();
							echo "sentiment++\n";
							$twitter_message = TwitterMessage::create();
							
							$twitter_message->setSearchId($this->getId());
							$twitter_message->setTwitterMessageId($res->id);
							if(!($profile = TwitterProfile::getTwitterProfileFromTwitterId($res->from_user_id)))
							{
								echo 'Create new TwitterProfile '.$res->from_user."\n";
								$profile = TwitterProfile::create();
							}
							$profile->setTwitterId($res->from_user_id);
							$profile->setScreenName($res->from_user);
							$profile->setProfileImageURL($res->profile_image_url);
							echo "influence\n";
							$name=$res->from_user;
							$profile->processInfluence($name);
							$twitter_message->setTwitterProfileFrom($profile);
							
							if(!($profile = TwitterProfile::getTwitterProfileFromTwitterId($res->to_user_id)))
							{
								echo 'Create new TwitterProfile '.$res->to_user."\n";
								$profile = TwitterProfile::create();
							}
							$profile->setTwitterId($res->to_user_id);
							$profile->setScreenName($res->screen_name);
							echo "influence\n";
							$name=$res->from_user;
							$profile->processInfluence($name);
							$twitter_message->setTwitterProfileTo($profile);
							
							$twitter_message->setText($res->text);
							$twitter_message->setCreatedAt(date('Y-m-d H:i:s', strtotime($res->created_at)));
							$twitter_message->setISOLanguageCode($res->iso_language_code);
							$twitter_message->setSource($res->source);
							
							$twitter_message->setSentiment(0);
							//$twitter_message->setProfileImageURL($res->profile_image_url);
							
							//If the tweet has geo set, try and use it
							if(!is_null($res->geo))
							{
								$twitter_message->setLatitude($res->geo->coordinates[0]);
								$twitter_message->setLongitude($res->geo->coordinates[1]);
								$twitter_message->setPrecision('twitter_coord');
							}
							else
							{	//if the location is not in the database...
								if (!CachedLocation::checkIfLocationExists($res->location))
								{	//and it is set
									if (!is_null($res->location))
									{	//add it to the database
										$cachedLoc=CachedLocation::create();
										$cachedLoc->setLocationString($res->location);
									}
								}
								else
								{	//if the location is in the database, add the loc data to the message
									$cl = CachedLocation::createLocationFromString($res->location);
									$twitter_message->setLatitude($cl->getLatitude());
									$twitter_message->setLongitude($cl->getLongitude());
									$twitter_message->setPrecision($cl->getPrecision());
									echo "Location added for Twitter Message. \n";
								}
								//$twitter_message->setLatLongFromLocation($res->location);
							}
							//store the metadata
							$twitter_message->setGeo(serialize($res->geo));
							$twitter_message->setLocation($res->location);
							$twitter_message->setLocationSerial(serialize($res->location));
							
							foreach($positive as $pos)
							{
								if(stripos($res->text, $pos))
								{
									$twitter_message->incrimentSentiment();
									echo "sentiment++\n";
								}
							}
							foreach($negative as $neg)
							{
								if(stripos($res->text, $neg))
								{
									$twitter_message->decrimentSentiment();
									echo "sentiment--\n";
								}
							}
							//$thread->findLocation();
							echo "New message found:  ".$res->id."  TwitterMessage Created: ".$twitter_message->getId()."\n";
						}
					}
					foreach($negative as $neg)
					{
						if(stripos($res->text, $neg))
						{
							$twitter_message->decrimentSentiment();
							echo "sentiment--\n";
						}
					}
					//$thread->findLocation();
					echo "New message found:  ".$res->id."  TwitterMessage Created: ".$twitter_message->getId()."\n";
				}
			}
		}
		catch(Exception $e)
		{
			$tw=new Service('1');
			$tw->setApiDown(date('Y-m-d H:i:s'));
		}
	}
	
	
	public function getDelay()
	{
		return $this->queryForValue('delay');
	}
	
	public function getDelaySeconds()
	{
		return $this->getDelay() * 60;
	}
	
	public function getFilter()
	{
		return $this->queryForValue('filter');
	}
	
	public function getFrequency()
	{
		return $this->queryForValue('frequency');
	}
	
	public function getFrequencySeconds()
	{
		return $this->getFrequency() * 60;
	}
	
	public function getGeocode()
	{
		return $this->queryForValue('geocode');
	}
	
	public function getLastExecuted()
	{
		return $this->queryForValue('last_executed');
	}
	
	public function getMaxThreadTwitterId()
	{
		$query = "SELECT max(`twitter_message_id`) as `val` FROM `twitter_message` WHERE `id_search` = '".mysql_real_escape_string($this->getId())."' AND `active` = '1'";
		$res = self::query($query);
		if(mysql_num_rows($res))
		{
			$res = mysql_fetch_array($res);
			return $res['val'];
		}
		return false;
	}
	
	/**
	 * @todo Fix or remove this function.  It broken
	 */
	public function getNegativeSentimentCountForDay($date = false)
	{
		if(!$date)
		{
			$date = date('Y-m-d');
		}
		
		$query = "SELECT `negative_count` FROM `v_daily_search_sentiment_total` WHERE `id_search` = '".mysql_real_escape_string($this->getId())."' AND `date` = '".mysql_real_escape_string($date)."'";
		$res = self::query($query);
		if(mysql_num_rows($res) > 0)
		{
			$res = mysql_fetch_array($res);
			return $res['negative_count'];
		}
		return false;
	}

	/**
	 * @todo Fix or remove this function.  It broken
	 */
	public function getNegativeSentimentSumForDay($date = false)
	{
		if(!$date)
		{
			$date = date('Y-m-d');
		}
		
		$query = "SELECT `negative` FROM `v_daily_search_sentiment_total` WHERE `id_search` = '".$this->getId()."' AND `date` = '".mysql_real_escape_string($date)."'";
		$res = self::query($query);
		if(mysql_num_rows($res) > 0)
		{
			$res = mysql_fetch_array($res);
			return $res['negative'];
		}
		return false;
	}

	/**
	 * @todo Fix or remove this function.  It broken
	 */
	public function getNeutralSentimentCountForDay($date = false)
	{
		if(!$date)
		{
			$date = date('Y-m-d');
		}
		
		$query = "SELECT `neutral_count` FROM `v_daily_search_sentiment_total` WHERE `id_search` = '".$this->getId()."' AND `date` = '".mysql_real_escape_string($date)."'";
		$res = self::query($query);
		if(mysql_num_rows($res) > 0)
		{
			$res = mysql_fetch_array($res);
			return $res['neutral_count'];
		}
		return false;
	}
	
	/**
	 * @todo Fix or remove this function.  It broken
	 */
	public function getPositiveSentimentCountForDay($date = false)
	{
		if(!$date)
		{
			$date = date('Y-m-d');
		}
		
		$query = "SELECT `positive_count` FROM `v_daily_search_sentiment_total` WHERE `id_search` = '".$this->getId()."' AND `date` = '".mysql_real_escape_string($date)."'";
		$res = self::query($query);
		if(mysql_num_rows($res) > 0)
		{
			$res = mysql_fetch_array($res);
			return $res['positive_count'];
		}
		return false;
	}
	
	/**
	 * @todo Fix or remove this function.  It broken
	 */
	public function getPositiveSentimentSumForDay($date = false)
	{
		if(!$date)
		{
			$date = date('Y-m-d');
		}
		
		$query = "SELECT `positive` FROM `v_daily_search_sentiment_total` WHERE `id_search` = '".$this->getId()."' AND `date` = '".mysql_real_escape_string($date)."'";
		$res = self::query($query);
		if(mysql_num_rows($res) > 0)
		{
			$res = mysql_fetch_array($res);
			return $res['positive'];
		}
		return false;
	}
	
	public function getQuery()
	{
		return $this->queryForValue('query');
	}
	
	public function getQueueCount()
	{
		$query = "SELECT count(`id_search`) as `val` FROM `message` WHERE `id_search` = '".$this->getId()."' AND `sent_time` IS NULL AND `active` = '1'";
		$res = self::query($query);
		$res = mysql_fetch_array($res);
		return $res['val'];
	}
	
	public function getResponse()
	{
		return $this->queryForValue('response');
	}
	
	public static function getSearchesToExecute()
	{
		$query = "SELECT `id_search` AS `val` FROM `search` WHERE `active` = '1'";
		$res = self::query($query);
		
		$return_array = array();
		
		if(mysql_num_rows($res) > 0)
		{
			while($row = mysql_fetch_array($res))
			{
				$search = new Search($row['val']);
				if($search->isToBeExecuted())
				{
					$return_array[] = $search;
				}
				unset($search);
			}
		}
		return $return_array;
	}
	
	public function getSentCount()
	{
		$query = "SELECT count(`id_search`) as `val` FROM `message` WHERE `id_search` = '".$this->getId()."' AND `sent_time` IS NOT NULL AND `active` = '1'";
		$res = self::query($query);
		$res = mysql_fetch_array($res);
		return $res['val'];
	}
	
	public function getProject()
	{
		return new Project($this->queryForValue('id_project'));
	}
	
	public function isToBeExecuted()
	{
		if(strtotime($this->getLastExecuted()) + $this->getFrequencySeconds() <= time())
		{
			return true;
		}
		return false;
	}
	
	public function setDelay($delay)
	{
		return $this->updateValue('delay', $delay);
	}
	
	public function setFilter($filter)
	{
		return $this->updateValue('filter', $filter);
	}
	
	public function setFrequency($frequency)
	{
		return $this->updateValue('frequency', $frequency);
	}
	
	public function setGeocode($geocode)
	{
		return $this->updateValue('geocode', $geocode);
	}
	
	public function setLastExecuted($last_executed)
	{
		return $this->updateValue('last_executed', $last_executed);
	}
	
	public function setQuery($query)
	{
		return $this->updateValue('query', $query);
	}
	
	public function setResponse($response)
	{
		return $this->updateValue('response', $response);
	}
	
	public function setProject($project)
	{
		return $this->updateValue('id_project', $project->getId());
	}
	
	public function twitterMessageExists($twitter_message_id)
	{
		$query = "SELECT * FROM `twitter_message` WHERE `id_search` = '".mysql_real_escape_string($this->getId())."' AND `twitter_message_id` = '".mysql_real_escape_string($twitter_message_id)."'";
		$res = self::query($query);
		if(mysql_num_rows($res) > 0)
		{
			return true;
		}
		return false;
	}
}

?>
